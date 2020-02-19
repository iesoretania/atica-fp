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

namespace AppBundle\Controller\Organization\Import;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Department;
use AppBundle\Form\Model\DepartmentImport;
use AppBundle\Form\Type\Import\DepartmentImportType;
use AppBundle\Repository\Edu\DepartmentRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DepartmentImportController extends Controller
{
    /**
     * @Route("/centro/importar/departamento", name="organization_import_department_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        DepartmentRepository $departmentRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new DepartmentImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(DepartmentImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getAcademicYear(),
                $teacherRepository,
                $departmentRepository,
                $entityManager,
                [
                    'extract_heads' => $formData->isExtractHeads()
                ]
            );

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.department.import', [], 'import');

        return $this->render('admin/organization/import/department_import_form.html.twig', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }

    /**
     * @param string $file
     * @param AcademicYear $academicYear
     * @param TeacherRepository $teacherRepository
     * @param DepartmentRepository $departmentRepository
     * @param EntityManagerInterface $entityManager
     * @param array $options
     * @return array|null
     */
    private function importFromCsv(
        $file,
        AcademicYear $academicYear,
        TeacherRepository $teacherRepository,
        DepartmentRepository $departmentRepository,
        EntityManagerInterface $entityManager,
        $options = []
    ) {
        $newCount = 0;
        $oldCount = 0;

        $importer = new CsvImporter($file, true);

        $collection = [];

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $departmentData) {
                    if (!isset($departmentData['Descripción'])) {
                        return null;
                    }
                    $departmentName = $departmentData['Descripción'];

                    $department = $departmentRepository->findOneBy([
                        'internalCode' => $departmentName,
                        'academicYear' => $academicYear
                    ]);


                    if (null === $department) {
                        $department = new Department();
                        $department
                            ->setAcademicYear($academicYear)
                            ->setInternalCode($departmentName)
                            ->setName($departmentName);

                        $entityManager->persist($department);
                        $newCount++;
                    } else {
                        $oldCount++;
                    }

                    // jefaturas de departamento
                    if ($options['extract_heads']) {
                        $headName = $departmentData['Jefe de departamento'];
                        $head = $teacherRepository->findByAcademicYearAndInternalCode($academicYear, $headName);
                        if ($head) {
                            $department->setHead($head);
                        }
                    }
                    $collection[] = $department;
                }
            }
            $entityManager->flush();
        } catch (Exception $e) {
            return null;
        }

        // ordenar por nombre antes de devolverlo
        usort($collection, function (Department $a, Department $b) {
            if ($a->getName() === $b->getName()) {
                return 0;
            }
            return ($a->getName() < $b->getName()) ? -1 : 1;
        });

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount,
            'collection' => $collection
        ];
    }
}
