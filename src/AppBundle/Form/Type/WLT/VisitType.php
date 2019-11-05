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

namespace AppBundle\Form\Type\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WLT\Project;
use AppBundle\Entity\WLT\Visit;
use AppBundle\Entity\Workcenter;
use AppBundle\Repository\Edu\StudentEnrollmentRepository;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\WLTStudentEnrollmentRepository;
use AppBundle\Repository\WLT\WLTTeacherRepository;
use AppBundle\Repository\WorkcenterRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisitType extends AbstractType
{
    /**
     * @var WorkcenterRepository
     */
    private $workcenterRepository;
    /**
     * @var WLTTeacherRepository
     */
    private $wltTeacherRepository;
    /**
     * @var UserExtensionService
     */
    private $userExtensionService;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;
    /**
     * @var StudentEnrollmentRepository
     */
    private $wltStudentEnrollmentRepository;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        WLTTeacherRepository $wltTeacherRepository,
        ProjectRepository $projectRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->projectRepository = $projectRepository;
        $this->userExtensionService = $userExtensionService;
        $this->wltStudentEnrollmentRepository = $wltStudentEnrollmentRepository;
    }

    private function addElements(
        FormInterface $form,
        AcademicYear $academicYear = null,
        Workcenter $workcenter = null,
        $selectedProjects = [],
        $teachers = [],
        \DateTime $dateTime = null
    ) {
        $workcenters = $this->workcenterRepository->findAll();

        if ($academicYear &&
            $academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            if (!$teachers) {
                $teachers = $this->wltTeacherRepository->findByAcademicYearAndWLT($academicYear);
            }
        } else {
            $teachers = [];
        }
        $studentEnrollments = [];
        if ($workcenter) {
            $projects = $this->projectRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
            if (count($projects) > 0) {
                $studentEnrollments =
                    $this
                        ->wltStudentEnrollmentRepository
                        ->findByWorkcenterProjectsAndDate($workcenter, $selectedProjects, $dateTime);
            }
        } else {
            $projects = [];
        }
        $canSelectProjects = count($projects) > 0;
        $canSelectStudentEnrollments = count($studentEnrollments) > 0;

        $form
            ->add('dateTime', DateTimeType::class, [
                'label' => 'form.datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'model_timezone' => 'UTC',
                'required' => true
            ])
            ->add('teacher', EntityType::class, [
                'label' => 'form.teacher',
                'class' => Teacher::class,
                'choices' => $teachers,
                'placeholder' => 'form.teacher.none',
                'required' => true
            ])
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choices' => $workcenters,
                'placeholder' => 'form.workcenter.none',
                'required' => true
            ])
            ->add('projects', EntityType::class, [
                'label' => 'form.projects',
                'class' => Project::class,
                'choices' => $projects,
                'disabled' => !$canSelectProjects,
                'expanded' => $canSelectProjects,
                'mapped' => $canSelectProjects,
                'multiple' => $canSelectProjects,
                'placeholder' => 'form.projects.none',
                'required' => false
            ])
            ->add('studentEnrollments', EntityType::class, [
                'label' => 'form.student_enrollments',
                'class' => StudentEnrollment::class,
                'choices' => $studentEnrollments,
                'disabled' => !$canSelectStudentEnrollments,
                'expanded' => $canSelectStudentEnrollments,
                'mapped' => $canSelectStudentEnrollments,
                'multiple' => $canSelectStudentEnrollments,
                'placeholder' => 'form.student_enrollments.none',
                'required' => false
            ])
            ->add('detail', TextareaType::class, [
                'label' => 'form.detail',
                'required' => false,
                'attr' => ['rows' => 10]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data->getTeacher()) {
                $academicYear = $data->getTeacher()->getAcademicYear();
            } else {
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            $this->addElements(
                $form,
                $academicYear,
                $data->getWorkcenter(),
                $data->getProjects(),
                $options['teachers'],
                $data->getDateTime()
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['teacher']) {
                $academicYear = $this->wltTeacherRepository->find($data['teacher'])->getAcademicYear();
            } else {
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            if ($data['workcenter']) {
                /** @var Workcenter $workcenter */
                $workcenter = $this->workcenterRepository->find($data['workcenter']);
                $selectedProjects = isset($data['projects'])
                    ? $this->projectRepository->findByIds($data['projects'])
                    : [];
            } else {
                $workcenter = null;
                $selectedProjects = [];
            }

            dump($data);
            $this->addElements(
                $form,
                $academicYear,
                $workcenter,
                $selectedProjects,
                $options['teachers'],
                date_create($data['dateTime']['date'] . ' ' . $data['dateTime']['time'])
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Visit::class,
            'teachers' => [],
            'translation_domain' => 'wlt_visit'
        ]);
    }
}
