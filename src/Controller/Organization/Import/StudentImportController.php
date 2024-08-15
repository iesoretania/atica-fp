<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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
use App\Form\Model\StudentLoginImport;
use App\Form\Type\Import\StudentImportType;
use App\Form\Type\Import\StudentLoginImportType;
use App\Repository\Edu\GroupRepository;
use App\Repository\Edu\StudentEnrollmentRepository;
use App\Repository\PersonRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use App\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StudentImportController extends AbstractController
{
    #[Route(path: '/centro/importar/estudiante', name: 'organization_import_student_form', methods: ['GET', 'POST'])]
    public function index(
        UserExtensionService $userExtensionService,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        GroupRepository $groupRepository,
        PersonRepository $personRepository,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordEncoder,
        Request $request
    ): Response {
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
                $passwordEncoder,
                [
                    'overwrite_usernames' => $formData->getOverwriteUserNames()
                ]
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
     */
    private function importFromCsv(
        $file,
        AcademicYear $academicYear,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        GroupRepository $groupRepository,
        PersonRepository $personRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordEncoder,
        array $options = []
    ): ?array {
        $newCount = 0;
        $oldCount = 0;

        $importer = new CsvImporter($file, true);

        $collection = [];

        $personCollection = [];

        $porDefecto = $passwordEncoder->hashPassword(new Person(), $academicYear->getOrganization()->getCode());
        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $studentData) {
                    if (!isset($studentData['Alumno/a'])) {
                        return null;
                    }

                    // ignorar bajas y matrículas anuladas o trasladadas
                    if ($studentData['Estado Matrícula']) {
                        continue;
                    }

                    $groupName = $studentData['Unidad'];
                    $group = $groupRepository->findOneByAcademicYearAndNameOrInternalCode(
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
                    if (!$person instanceof Person) {
                        $gender = $studentData['Sexo'];
                        $gender = match ($gender) {
                            'H' => Person::GENDER_MALE,
                            'M' => Person::GENDER_FEMALE,
                            default => Person::GENDER_NEUTRAL,
                        };

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
                    } elseif ($options['overwrite_usernames'] ?? false) {
                        $person->setLoginUsername($uniqueIdentifier1);
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
        } catch (Exception) {
            return null;
        }

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount,
            'collection' => $collection
        ];
    }

    #[Route(path: '/centro/importar/estudiante/login', name: 'organization_import_student_login_form', methods: ['GET', 'POST'])]
    public function login(
        UserExtensionService $userExtensionService,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new StudentLoginImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(StudentLoginImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importLoginFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getAcademicYear(),
                $studentEnrollmentRepository,
                $entityManager
            );

            if (!isset($stats['error'])) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error' . $stats['error'], [], 'import'));
            }
        }
        $title = $translator->trans('title.student_login.import', [], 'import');

        return $this->render('admin/organization/import/student_login_import_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }
    /**
     * @param string $file
     * @param array $options
     */
    private function importLoginFromCsv(
        $file,
        AcademicYear $academicYear,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        EntityManagerInterface $entityManager
    ): array {
        $updatedCount = 0;
        $totalCount = 0;

        $importer = new CsvImporter($file, true);

        $collection = [];

        $conflicts = [];

        $lastName = '';
        $lastUsername = '';
        $conflict = false;
        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $studentData) {
                    $totalCount++;
                    if (!isset($studentData['Nombre']) || !isset($studentData['Usuario IdEA']) || !isset($studentData['Activo en Séneca'])) {
                        return ['error' => '_missing_columns'];
                    }

                    // ignorar estudiantes no activos
                    if ($studentData['Activo en Séneca'] !== 'Sí') {
                        continue;
                    }

                    $name = $studentData['Nombre'];
                    $username = $studentData['Usuario IdEA'];

                    if ($name === $lastName) {
                        if (!$conflict) {
                            $conflicts[] = [
                                'name' => $lastName,
                                'username' => $lastUsername,
                                'student_enrollments' => null
                            ];
                            $conflict = true;
                        }
                        $conflicts[] = [
                            'name' => $name,
                            'username' => $username,
                            'student_enrollments' => null
                        ];
                        $lastUsername = $studentData['Usuario IdEA'];
                        continue;
                    }

                    $conflict = false;
                    $lastName = $name;
                    $lastUsername = $username;

                    $pieces = preg_split('/\,\ /', (string) $name, 2);

                    $studentEnrollments = $studentEnrollmentRepository->findByAcademicYearNameAndSurname(
                        $academicYear,
                        $pieces[1],
                        $pieces[0]
                    );

                    if (count($studentEnrollments) == 0) {
                        continue;
                    }

                    if (count($studentEnrollments) > 1) {
                        $conflicts[] = [
                            'name' => $name,
                            'username' => $username,
                            'student_enrollments' => $studentEnrollments
                        ];
                        continue;
                    }

                    /** @var StudentEnrollment $studentEnrollment */
                    $studentEnrollment = $studentEnrollments[0];
                    $studentEnrollment->getPerson()->setLoginUsername($username);
                    $studentEnrollment->getPerson()->setAllowExternalCheck(true);
                    $studentEnrollment->getPerson()->setExternalCheck(true);

                    $collection[] = [
                        'name' => $name,
                        'username' => $username,
                        'student_enrollment' => $studentEnrollment
                    ];

                    $updatedCount++;
                }
            }
            $entityManager->flush();
        } catch (QueryException) {
            return ['error' => '_query'];
        } catch (Exception) {
            return ['error' => ''];
        }

        return [
            'updated_items' => $updatedCount,
            'total_items' => $totalCount,
            'collection' => $collection,
            'conflicts' => $conflicts
        ];
    }
}
