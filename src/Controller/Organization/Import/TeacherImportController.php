<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace App\Controller\Organization\Import;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Organization;
use App\Entity\Person;
use App\Form\Model\TeacherImport;
use App\Form\Type\Import\TeacherImportType;
use App\Repository\PersonRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use App\Utils\CsvImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TeacherImportController extends AbstractController
{
    /**
     * @Route("/centro/importar/profesorado", name="organization_import_teacher_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $passwordEncoder,
        PersonRepository $personRepository,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new TeacherImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(TeacherImportType::class, $formData, [
            'organization' => $organization
        ]);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importTeachersFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getAcademicYear(),
                $passwordEncoder,
                $personRepository,
                [
                    'generate_password' => $formData->getGeneratePassword(),
                    'external_check' => $formData->isExternalPassword()
                ]
            );

            if (null !== $stats) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error', [], 'import'));
            }
        }
        $title = $translator->trans('title.teacher.import', [], 'import');

        return $this->render('admin/organization/import/teacher_import_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'generate_password' => $formData->getGeneratePassword(),
            'stats' => $stats
        ]);
    }

    /**
     * @param string $file
     * @param Organization $organization
     * @param AcademicYear $academicYear
     * @param array $options
     * @return array|null
     */
    private function importTeachersFromCsv(
        $file,
        AcademicYear $academicYear,
        UserPasswordEncoderInterface $encoder,
        PersonRepository $personRepository,
        array $options = []
    ) {
        $generatePassword = isset($options['generate_password']) && $options['generate_password'];
        $external = isset($options['external_check']) && $options['external_check'];
        $newUserCount = 0;
        $existingUsers = 0;

        $em = $this->getDoctrine()->getManager();

        $importer = new CsvImporter($file, true);

        $personCollection = [];
        $newPersonCollection = [];

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $personData) {
                    if (!isset($personData['Usuario IdEA'])) {
                        return null;
                    }
                    $userName = $personData['Usuario IdEA'];

                    $alreadyProcessed = isset($personCollection[$userName]);

                    if ($alreadyProcessed) {
                        $person = $personCollection[$userName];
                        $existingUsers++;
                    } else {
                        $person = $personRepository->findOneByUniqueIdentifier($personData['DNI/Pasaporte']);

                        if (null === $person) {
                            $person = new Person();

                            $fullName = explode(', ', $personData['Empleado/a']);

                            $person
                                ->setFirstName($fullName[1])
                                ->setLastName($fullName[0])
                                ->setGender(Person::GENDER_NEUTRAL)
                                ->setUniqueIdentifier($personData['DNI/Pasaporte'])
                                ->setInternalCode($personData['Empleado/a'])
                                ->setLoginUsername($userName)
                                ->setEnabled(true)
                                ->setGlobalAdministrator(false)
                                ->setAllowExternalCheck($external)
                                ->setExternalCheck($external);

                            if ($generatePassword) {
                                $person
                                    ->setPassword($encoder->encodePassword($person, $personData['Usuario IdEA']))
                                    ->setForcePasswordChange(true);
                            }

                            $em->persist($person);
                            $newPersonCollection[$userName] = $person;
                            $newUserCount++;
                        }
                        else {
                             $existingUsers++;
                        }

                        $personCollection[$userName] = $person;
                    }

                    $teacher = $person->getId() ? $em->getRepository(Teacher::class)->findOneBy([
                            'academicYear' => $academicYear,
                            'person' => $person
                        ]) : null;

                    if (null === $teacher) {
                        $teacher = new Teacher();
                        $teacher
                            ->setAcademicYear($academicYear)
                            ->setPerson($person);
                        $em->flush(); // hack, o no funciona con nuevos profesores. TODO: Investigar
                        $em->persist($teacher);
                    }
                }
            }
            $em->flush();

            $em->flush();
        } catch (Exception $e) {
            return null;
        }

        return [
            'new_user_count' => $newUserCount,
            'existing_user_count' => $existingUsers,
            'user_collection' => $newPersonCollection
        ];
    }
}
