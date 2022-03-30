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
use App\Entity\Edu\Group;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Person;
use App\Form\Model\StudentImport;
use App\Form\Type\Import\StudentImportType;
use App\Repository\Edu\GroupRepository;
use App\Repository\Edu\StudentEnrollmentRepository;
use App\Repository\PersonRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use App\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StudentImportController extends AbstractController
{
    /**
     * @Route("/centro/importar/estudiante", name="organization_import_student_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        GroupRepository $groupRepository,
        PersonRepository $personRepository,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new StudentImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(StudentImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getAcademicYear(),
                $studentEnrollmentRepository,
                $groupRepository,
                $personRepository,
                $entityManager,
                $passwordEncoder
            );

            if (null !== $stats) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error', [], 'import'));
            }
        }
        $title = $translator->trans('title.student.import', [], 'import');

        return $this->render('admin/organization/import/student_import_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }

    /**
     * @param string $file
     * @param AcademicYear $academicYear
     * @param StudentEnrollmentRepository $studentEnrollmentRepository
     * @param GroupRepository $groupRepository
     * @param PersonRepository $personRepository
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param array $options
     * @return array|null
     */
    private function importFromCsv(
        $file,
        AcademicYear $academicYear,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        GroupRepository $groupRepository,
        PersonRepository $personRepository,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $newCount = 0;
        $oldCount = 0;

        $importer = new CsvImporter($file, true);

        $collection = [];

        $personCollection = [];

        $porDefecto = $passwordEncoder->encodePassword(new Person(), $academicYear->getOrganization()->getCode());
        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $studentData) {
                    if (!isset($studentData['Alumno/a'])) {
                        return null;
                    }

                    // ignorar bajas y matriculas anuladas o trasladadas
                    if ($studentData['Estado Matrícula']) {
                        continue;
                    }

                    $groupName = $studentData['Unidad'];
                    $group = $groupRepository->findOneByAcademicYearAndInternalCode(
                        $academicYear,
                        $groupName
                    );

                    // ignorar alumnado de grupos no existentes en la plataforma
                    if (!$group instanceof Group) {
                        continue;
                    }

                    $uniqueIdentifier1 = $studentData['Nº Id. Escolar'];
                    $uniqueIdentifier2 = $studentData['DNI/Pasaporte'];

                    if (!isset($personCollection[$uniqueIdentifier1])) {
                        $person = $personRepository->findOneByUniqueIdentifiers($uniqueIdentifier1, $uniqueIdentifier2);
                    } else {
                        $person = $personCollection[$uniqueIdentifier1];
                    }
                    if (null === $person) {
                        $gender = $studentData['Sexo'];
                        switch ($gender) {
                            case 'H':
                                $gender = Person::GENDER_MALE;
                                break;
                            case 'M':
                                $gender = Person::GENDER_FEMALE;
                                break;
                            default:
                                $gender = Person::GENDER_NEUTRAL;
                        }

                        $person = new Person();
                        $person
                            ->setFirstName($studentData['Nombre'])
                            ->setLastName(
                                $studentData['Primer apellido'] .
                                ($studentData['Segundo apellido'] ? (' ' . $studentData['Segundo apellido']) : '')
                            )
                            ->setGender($gender);

                        $entityManager->persist($person);
                        $newCount++;
                        $enrollment = null;
                    } else {
                        $oldCount++;
                        $enrollment = $studentEnrollmentRepository->findOneBy([
                            'person' => $person,
                            'group' => $group
                        ]);
                    }
                    $person->setUniqueIdentifier($uniqueIdentifier1);
                    if ($person->getLoginUsername() === null) {
                        $person
                            ->setLoginUsername($uniqueIdentifier1)
                            ->setPassword($porDefecto)
                            ->setEnabled(true)
                            ->setForcePasswordChange(true);
                    }
                    $personCollection[$uniqueIdentifier1] = $person;

                    if (null === $enrollment) {
                        $enrollment = new StudentEnrollment();
                        $enrollment
                            ->setPerson($person)
                            ->setGroup($group);

                        $entityManager->persist($enrollment);
                    }
                    $collection[] = $enrollment;
                }
            }
            $entityManager->flush();
        } catch (Exception $e) {
            return null;
        }

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount,
            'collection' => $collection
        ];
    }
}
