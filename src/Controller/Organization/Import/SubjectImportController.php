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
use App\Entity\Edu\Subject;
use App\Entity\Edu\Teaching;
use App\Form\Model\SubjectImport;
use App\Form\Type\Import\SubjectImportType;
use App\Repository\Edu\GradeRepository;
use App\Repository\Edu\GroupRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\Edu\TeachingRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use App\Utils\CsvImporter;
use App\Utils\ImportParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubjectImportController extends AbstractController
{
    /**
     * @Route("/centro/importar/materia", name="organization_import_subject_form", methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        SubjectRepository $subjectRepository,
        GradeRepository $gradeRepository,
        GroupRepository $groupRepository,
        TeachingRepository $teachingRepository,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $formData = new SubjectImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());
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
                    'keep_one_subject_per_training' => $formData->isKeepOneSubjectPerTraining(),
                    'extract_teachers' => $formData->isExtractTeachers()
                ]
            );

            if (null !== $stats) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error', [], 'import'));
            }
        }
        $title = $translator->trans('title.subject.import', [], 'import');

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

        $teachingCollection = [];
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
                    $subjectName = trim($subjectData['Materia']);
                    $subjectName = preg_replace('/\ +/', ' ', $subjectName, -1);
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
                        $indexSubject = $subjectName . '.' . $gradeName;
                        if (false === isset($subjectCollection[$indexSubject])) {
                            $subject = $subjectRepository->findOneByAcademicYearAndInternalCode(
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
                            $subject = $subjectCollection[$indexSubject];
                        }

                        if (null === $subject) {
                            $grade = null;
                            if (false === isset($gradeCollection[$gradeName])) {
                                $grade = $gradeRepository->
                                    findOneByAcademicYearAndInternalCode($academicYear, $gradeName);
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

                                $subjectCollection[$indexSubject] = $subject;

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
                                $teacher = $teacherRepository->
                                    findByAcademicYearAndInternalCode($academicYear, $teacherName);
                                $teacherCollection[$teacherName] = $teacher;
                            } else {
                                $teacher = $teacherCollection[$teacherName];
                            }

                            // si el profesor existe
                            if ($teacher && $subject) {
                                $indexSubject = $teacherName . '.' . $groupName . '.' . $subjectName;
                                if (false === isset($teachingCollection[$indexSubject])) {
                                    $teaching = $teachingRepository->findOneBy(
                                        [
                                            'teacher' => $teacher,
                                            'group' => $group,
                                            'subject' => $subject
                                        ]
                                    );
                                } else {
                                    $teaching = $teachingCollection[$indexSubject];
                                }

                                // comprobar si existe la asignación y, si no, la crea
                                if (null === $teaching) {
                                    $teaching = new Teaching();
                                    $teaching
                                        ->setTeacher($teacher)
                                        ->setGroup($group)
                                        ->setSubject($subject);

                                    $entityManager->persist($teaching);
                                }

                                $teachingCollection[$indexSubject] = $teaching;
                            }
                        }
                    }
                }
            }
            $entityManager->flush();

            $deletedList = [];

            // comprobar si hay que eliminar las materias duplicadas en cursos inferiores
            // (para los pendientes en FP)
            if ($options['keep_one_subject_per_training']) {
                $subjects = $subjectRepository->findByAcademicYearAndTrainingFilterOrdered($academicYear, 'F.P.%');

                $lastSubjectName = '';
                $lastTrainingName = '';

                foreach ($subjects as $subject) {
                    if ($lastSubjectName === $subject->getName() &&
                        $lastTrainingName === $subject->getGrade()->getTraining()) {
                        $deletedList[] = $subject;
                    }
                    $lastSubjectName = $subject->getName();
                    $lastTrainingName = $subject->getGrade()->getTraining();
                }

                $subjectRepository->deleteFromList($deletedList);

                $entityManager->flush();
            }
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
            'collection' => $collection,
            'deleted_list' => $deletedList
        ];
    }
}
