<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Controller\Organization\Import;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use AppBundle\Entity\User;
use AppBundle\Form\Model\TeacherImport;
use AppBundle\Form\Type\Import\TeacherImportType;
use AppBundle\Repository\MembershipRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class TeacherImportController extends Controller
{
    /**
     * @Route("/centro/importar/profesorado", name="organization_import_teacher_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        MembershipRepository $membershipRepository,
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
                $membershipRepository,
                $organization,
                $formData->getAcademicYear(),
                [
                    'generate_password' => $formData->getGeneratePassword(),
                    'external_check' => $formData->isExternalPassword()
                ]
            );

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.teacher.import', [], 'import');

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
     * @param MembershipRepository $membershipRepository
     * @param Organization $organization
     * @param AcademicYear $academicYear
     * @param array $options
     * @return array|null
     */
    private function importTeachersFromCsv(
        $file,
        MembershipRepository $membershipRepository,
        Organization $organization,
        AcademicYear $academicYear,
        $options = []
    ) {
        $generatePassword = isset($options['generate_password']) && $options['generate_password'];
        $external = isset($options['external_check']) && $options['external_check'];
        $newUserCount = 0;
        $newMemberships = 0;
        $existingUsers = 0;
        $existingMemberships = 0;

        $em = $this->getDoctrine()->getManager();

        $importer = new CsvImporter($file, true);
        $encoder = $this->container->get('security.password_encoder');

        $userCollection = [];
        $newUserCollection = [];

        $now = new \DateTime();

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $userData) {
                    if (!isset($userData['Usuario IdEA'])) {
                        return null;
                    }
                    $userName = $userData['Usuario IdEA'];

                    $alreadyProcessed = isset($userCollection[$userName]);

                    if ($alreadyProcessed) {
                        $user = $userCollection[$userName];
                        $person = $user->getPerson();
                        $existingUsers++;
                    } else {
                        $user = $em->getRepository(User::class)->findOneBy(['loginUsername' => $userName]);
                        if (null === $user) {
                            $user = new User();
                            $person = $em->getRepository(Person::class)->findOneBy([
                                'uniqueIdentifier' => $userData['DNI/Pasaporte']
                            ]);

                            if (null === $person) {
                                $person = new Person();

                                $fullName = explode(', ', $userData['Empleado/a']);

                                $person
                                    ->setFirstName($fullName[1])
                                    ->setLastName($fullName[0])
                                    ->setGender(User::GENDER_NEUTRAL);
                            }
                            $person
                                ->setUniqueIdentifier($userData['DNI/Pasaporte'])
                                ->setInternalCode($userData['Empleado/a'])
                                ->setUser($user);

                            $user
                                ->setLoginUsername($userName)
                                ->setEnabled(true)
                                ->setGlobalAdministrator(false)
                                ->setAllowExternalCheck($external)
                                ->setExternalCheck($external);

                            if ($generatePassword) {
                                $user
                                    ->setPassword($encoder->encodePassword($user, $userData['Usuario IdEA']));
                            }

                            $em->persist($person);
                            $em->persist($user);

                            $userCollection[$userName] = $user;
                            $newUserCollection[$userName] = $user;

                            $newUserCount++;
                        } else {
                            $person = $user->getPerson();
                            $existingUsers++;
                        }
                    }

                    $validFrom = \DateTime::createFromFormat(
                        'd/m/Y H:i:s',
                        $userData['Fecha de toma de posesión'] . '00:00:00'
                    );
                    $validUntil = ($userData['Fecha de cese']) ?
                        \DateTime::createFromFormat('d/m/Y H:i:s', $userData['Fecha de cese'] . '23:59:59') :
                        null;

                    if (false === $validFrom || (null !== $validUntil && $validUntil < $now)) {
                        continue;
                    }

                    /** @var Membership $membership */
                    $membership = $user->getId() ? $em->getRepository(Membership::class)->findOneBy([
                        'organization' => $organization,
                        'user' => $user,
                        'validFrom' => $validFrom
                    ]) : null;

                    if (null === $membership) {
                        $membership = new Membership();
                        $membership
                            ->setOrganization($organization)
                            ->setUser($user)
                            ->setValidFrom($validFrom)
                            ->setValidUntil($validUntil);

                        $em->persist($membership);

                        $newMemberships++;
                    } else {
                        $membership
                            ->setValidUntil($validUntil);

                        $existingMemberships++;
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
                        $em->persist($teacher);
                    }
                }
            }
            $em->flush();

            // por último, borrar pertenencias caducadas
            $membershipRepository->deleteOldMemberships(new \DateTime());

            $em->flush();
        } catch (Exception $e) {
            return null;
        }

        return [
            'new_user_count' => $newUserCount,
            'new_membership_count' => $newMemberships,
            'existing_user_count' => $existingUsers,
            'existing_membership_count' => $existingMemberships,
            'user_collection' => $newUserCollection
        ];
    }
}
