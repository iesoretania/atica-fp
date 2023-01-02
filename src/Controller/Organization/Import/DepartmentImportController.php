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
use App\Entity\Edu\Department;
use App\Form\Model\DepartmentImport;
use App\Form\Type\Import\DepartmentImportType;
use App\Repository\Edu\DepartmentRepository;
use App\Repository\Edu\TeacherRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use App\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DepartmentImportController extends AbstractController
{
    /**
     * @Route("/centro/importar/departamento", name="organization_import_department_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        DepartmentRepository $departmentRepository,
        TranslatorInterface $translator,
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

            if (!isset($stats['error'])) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error' . $stats['error'], [], 'import'));
            }
        }
        $title = $translator->trans('title.department.import', [], 'import');

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
                    if (!isset($departmentData['Descripción']) || !isset($departmentData['Jefe de departamento'])) {
                        return ['error' => '_missing_columns'];
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
        } catch (QueryException $e) {
            return ['error' => '_query'];
        } catch (Exception $e) {
            return ['error' => ''];
        }

        // ordenar por nombre antes de devolverlo
        usort($collection, function (Department $a, Department $b) {
            return $a->getName() <=> $b->getName();
        });

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount,
            'collection' => $collection
        ];
    }
}
