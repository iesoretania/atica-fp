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
use AppBundle\Entity\Edu\Grade;
use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\Edu\Training;
use AppBundle\Form\Model\GroupImport;
use AppBundle\Form\Type\Import\GroupImportType;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use AppBundle\Utils\ImportParser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GroupImportController extends Controller
{
    /**
     * @Route("/centro/importar/grupo", name="organization_import_group_form", methods={"GET", "POST"})
     */
    public function indexAction(UserExtensionService $userExtensionService, TeacherRepository $teacherRepository, Request $request)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new GroupImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(GroupImportType::class, $formData, [
            'organization' => $organization
        ]);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv($formData->getFile()->getPathname(), $formData->getAcademicYear(),
                $teacherRepository,
                [
                    'restricted' => $formData->isRestricted(),
                    'extract_tutors' => $formData->isExtractTutors()
                ]
            );

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.group.import', [], 'import');

        return $this->render('admin/organization/import/group_import_form.html.twig', [
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
     * @param array $options
     * @return array|null
     */
    private function importFromCsv($file, AcademicYear $academicYear, TeacherRepository $teacherRepository, $options = [])
    {
        $newCount = 0;
        $oldCount = 0;

        $restricted = isset($options['restricted']) && $options['restricted'];

        $em = $this->getDoctrine()->getManager();

        $importer = new CsvImporter($file, true);

        $trainingCollection = [];
        $gradeCollection = [];
        $groupCollection = [];
        $collection = [];

        // precargar los datos de la organización y del curso académico para evitar múltiples consultas
        $items = $em->getRepository(Training::class)->findBy(['academicYear' => $academicYear]);
        foreach($items as $item) {
            $trainingCollection[$item->getInternalCode()] = $item;
        }
        $items = $em->getRepository(Grade::class)->findByAcademicYear($academicYear);
        foreach($items as $item) {
            $gradeCollection[$item->getInternalCode()] = $item;
        }
        $items = $em->getRepository(Group::class)->findByAcademicYear($academicYear);
        foreach($items as $item) {
            $groupCollection[$item->getInternalCode()] = $item;
        }

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $groupData) {
                    if (!isset($groupData['Unidad'])) {
                        return null;
                    }
                    $groupName = $groupData['Unidad'];
                    $gradeName = $groupData['Curso'];

                    // Si se ha activado el modo restringido, sólo crear los grupos
                    // que contengan la cadena F.P. en curso
                    if ($restricted && false === strpos($gradeName, 'F.P.')) {
                        continue;
                    }

                    if (isset($gradeCollection[$gradeName])) {
                        $grade = $gradeCollection[$gradeName];
                    } else {
                        // Quedarnos con el primer elemento
                        $trainings = explode(',', $gradeName);
                        $gradeName = $trainings[0];

                        list($calculatedGradeName, $trainingName) = ImportParser::parseGradeName($gradeName);

                        if (isset($trainingCollection[$trainingName])) {
                            $training = $trainingCollection[$trainingName];
                        } else {
                            $training = $em->getRepository(Training::class)->findOneBy([
                                'internalCode' => $trainingName,
                                'academicYear' => $academicYear
                            ]);

                            if (null === $training) {
                                $training = new Training();
                                $training
                                    ->setAcademicYear($academicYear)
                                    ->setInternalCode($trainingName)
                                    ->setName($trainingName);

                                $em->persist($training);
                            }
                            $trainingCollection[$trainingName] = $training;
                        }

                        if (false === isset($gradeCollection[$calculatedGradeName])) {
                            if ($training->getId()) {
                                $grade = $em->getRepository(Grade::class)->findOneBy([
                                    'internalCode' => $calculatedGradeName,
                                    'training' => $training
                                ]);
                            } else {
                                $grade = null;
                            }
                        } else {
                            $grade = $gradeCollection[$calculatedGradeName];
                        }


                        if (null === $grade) {
                            $grade = new Grade();
                            $grade
                                ->setInternalCode($calculatedGradeName)
                                ->setName($calculatedGradeName)
                                ->setTraining($training);
                            $em->persist($grade);
                        }

                        $gradeCollection[$gradeName] = $grade;
                    }

                    $group = null;
                    if ($grade->getId()) {
                        if (false === isset($groupCollection[$groupName])) {
                            $group = $em->getRepository(Group::class)->findOneBy([
                                'internalCode' => $groupName,
                                'grade' => $grade
                            ]);
                        } else {
                            $group = $groupCollection[$groupName];
                        }
                    }

                    if (null === $group) {
                        $group = new Group();
                        $group
                            ->setName($groupName)
                            ->setInternalCode($groupName)
                            ->setGrade($grade);

                        $em->persist($group);
                        $newCount++;
                    } else {
                        $oldCount++;
                    }

                    // tutores
                    if ($options['extract_tutors']) {
                        $matches = [];
                        preg_match_all('/\b(.*) \(.*\)/U', $groupData['Tutor/a'], $matches, PREG_SET_ORDER, 0);

                        $matches = array_map(function($element) {
                            return $element[1];
                        }, $matches);
                        $matches = array_unique($matches);

                        if (null !== $matches) {
                            foreach ($matches as $tutor) {
                                $teacher = $teacherRepository->findByAcademicYearAndInternalCode($academicYear, $tutor);

                                if ($teacher && false === $group->getTutors()->contains($teacher)) {
                                    $group->getTutors()->add($teacher);
                                }
                            }
                        }
                    }
                    $groupCollection[$group->getInternalCode()] = $group;
                    $collection[] = $group;
                }
            }
            $em->flush();
        } catch (Exception $e) {
            return null;
        }

        // ordenar por nombre antes de devolverlo
        usort($collection, function (Group $a, Group $b) {
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
