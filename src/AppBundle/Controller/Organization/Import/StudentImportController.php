<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Person;
use AppBundle\Form\Model\StudentImport;
use AppBundle\Form\Type\Import\StudentImportType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\StudentEnrollmentRepository;
use AppBundle\Repository\PersonRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class StudentImportController extends Controller
{
    /**
     * @Route("/centro/importar/estudiante", name="organization_import_student_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        GroupRepository $groupRepository,
        PersonRepository $personRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new StudentImport();
        $formData->setAcademicYear($academicYearRepository->getCurrentByOrganization($organization));

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
                $entityManager
            );

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.student.import', [], 'import');

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
     * @param array $options
     * @return array|null
     */
    private function importFromCsv(
        $file,
        AcademicYear $academicYear,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        GroupRepository $groupRepository,
        PersonRepository $personRepository,
        EntityManagerInterface $entityManager
    ) {
        $newCount = 0;
        $oldCount = 0;

        $importer = new CsvImporter($file, true);

        $collection = [];

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
                    if (null === $group) {
                        continue;
                    }

                    $internalCode = $studentData['DNI/Pasaporte'] ?: $studentData['Nº Id. Escolar'];

                    $person = $personRepository->findOneBy([
                        'internalCode' => $internalCode
                    ]);

                    if (null === $person) {
                        $person = new Person();
                        $person
                            ->setInternalCode($internalCode)
                            ->setFirstName($studentData['Nombre'])
                            ->setLastName(
                                $studentData['Primer apellido'] .
                                ($studentData['Segundo apellido'] ? (' ' . $studentData['Segundo apellido']) : '')
                            )
                            ->setGender(Person::GENDER_NEUTRAL);

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
