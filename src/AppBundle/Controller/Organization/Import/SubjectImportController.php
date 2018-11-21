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
use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\Edu\Teaching;
use AppBundle\Form\Model\SubjectImport;
use AppBundle\Form\Type\Import\SubjectImportType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\GradeRepository;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\SubjectRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\Edu\TeachingRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use AppBundle\Utils\ImportParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

class SubjectImportController extends Controller
{
    /**
     * @Route("/centro/importar/materia", name="organization_import_subject_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TeacherRepository $teacherRepository,
        SubjectRepository $subjectRepository,
        GradeRepository $gradeRepository,
        GroupRepository $groupRepository,
        TeachingRepository $teachingRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new SubjectImport();
        $formData->setAcademicYear($academicYearRepository->getCurrentByOrganization($organization));

        $form = $this->createForm(SubjectImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getAcademicYear(),
                $teacherRepository,
                $subjectRepository,
                $gradeRepository,
                $groupRepository,
                $teachingRepository,
                $entityManager,
                [
                    'extract_teachers' => $formData->isExtractTeachers()
                ]
            );

            if (null !== $stats) {
                $this->addFlash('success', $this->get('translator')->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $this->get('translator')->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $this->get('translator')->trans('message.import_error', [], 'import'));
            }
        }
        $title = $this->get('translator')->trans('title.subject.import', [], 'import');

        return $this->render('admin/organization/import/subject_import_form.html.twig', [
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
     * @param SubjectRepository $subjectRepository
     * @param GradeRepository $gradeRepository
     * @param GroupRepository $groupRepository
     * @param TeachingRepository $teachingRepository
     * @param EntityManagerInterface $entityManager
     * @param array $options
     * @return array|null
     */
    private function importFromCsv(
        $file,
        AcademicYear $academicYear,
        TeacherRepository $teacherRepository,
        SubjectRepository $subjectRepository,
        GradeRepository $gradeRepository,
        GroupRepository $groupRepository,
        TeachingRepository $teachingRepository,
        EntityManagerInterface $entityManager,
        $options = []
    ) {
        $newCount = 0;
        $oldCount = 0;

        $importer = new CsvImporter($file, true);

        $subjectCollection = [];
        $teacherCollection = [];
        $gradeCollection = [];
        $groupCollection = [];
        $collection = [];

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $subjectData) {
                    if (!isset($subjectData['Materia'])) {
                        return null;
                    }
                    $subjectName = $subjectData['Materia'];
                    $groupName = $subjectData['Unidad'];

                    $group = null;
                    if (false === isset($groupCollection[$groupName])) {
                        $group = $groupRepository->findOneByAcademicYearAndInternalCode($academicYear, $groupName);
                        $groupCollection[$groupName] = $group;
                    } else {
                        $group = $groupCollection[$groupName];
                    }

                    // si el grupo existe
                    if ($group) {
                        list($gradeName, $trainingName) = ImportParser::parseGradeName($subjectData['Curso']);

                        $subject = null;
                        if (false === isset($subjectCollection[$subjectName . $gradeName])) {
                            $subject = $subjectRepository->findOneByAcademicYearAndInternalCodes(
                                $academicYear,
                                $subjectName,
                                $gradeName
                            );
                            $subjectCollection[$subjectName . $gradeName] = $subject;
                            if (null !== $subject) {
                                $collection[] = $subject;
                                $oldCount++;
                            }
                        } else {
                            $subject = $subjectCollection[$subjectName . $gradeName];
                        }

                        if (null === $subject) {
                            $grade = null;
                            if (false === isset($gradeCollection[$gradeName])) {
                                $grade = $gradeRepository->findOneByAcademicYearAndInternalCode($academicYear, $gradeName);
                                $gradeCollection[$gradeName] = $grade;
                            } else {
                                $grade = $gradeCollection[$gradeName];
                            }

                            if ($grade) {
                                $subject = new Subject();
                                $subject
                                    ->setGrade($grade)
                                    ->setInternalCode($subjectName)
                                    ->setName($subjectName);

                                $subjectCollection[$subjectName . $gradeName] = $subject;

                                $entityManager->persist($subject);
                                $collection[] = $subject;
                                $newCount++;
                            }
                        }

                        // profesorado
                        if ($options['extract_teachers']) {
                            $teacherName = $subjectData['Profesor/a'];

                            $teacher = null;
                            if (false === isset($teacherCollection[$teacherName])) {
                                $teacher = $teacherRepository->findByAcademicYearAndInternalCode($academicYear, $teacherName);
                                $teacherCollection[$teacherName] = $teacher;
                            } else {
                                $teacher = $teacherCollection[$teacherName];
                            }

                            // si el profesor existe
                            if ($teacher && $subject) {
                                $teaching = $teachingRepository->findOneBy(
                                    [
                                        'teacher' => $teacher,
                                        'group' => $group,
                                        'subject' => $subject
                                    ]
                                );

                                // comprobar si existe la asignación y, si no, la crea
                                if (null === $teaching) {
                                    $teaching = new Teaching();
                                    $teaching
                                        ->setTeacher($teacher)
                                        ->setGroup($group)
                                        ->setSubject($subject);

                                    $entityManager->persist($teaching);
                                }
                            }
                        }
                    }
                }
            }
            $entityManager->flush();
        } catch (Exception $e) {
            return null;
        }

        // ordenar por enseñanza, nivel y nombre antes de devolverlo
        usort($collection, function (Subject $a, Subject $b) {
            $aValue = $a->getGrade()->getTraining()->getName() . $a->getGrade()->getName() . $a->getName();
            $bValue = $b->getGrade()->getTraining()->getName() . $b->getGrade()->getName() . $b->getName();
            if ($aValue === $bValue) {
                return 0;
            }
            return ($aValue < $bValue) ? -1 : 1;
        });

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount,
            'collection' => $collection
        ];
    }
}
