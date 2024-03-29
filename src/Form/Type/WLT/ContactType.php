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

namespace App\Form\Type\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Contact;
use App\Entity\WLT\Project;
use App\Entity\Workcenter;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WLT\WLTStudentEnrollmentRepository;
use App\Repository\WLT\WLTTeacherRepository;
use App\Repository\WorkcenterRepository;
use App\Service\UserExtensionService;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    private $workcenterRepository;
    private $wltTeacherRepository;
    private $userExtensionService;
    private $projectRepository;
    private $wltStudentEnrollmentRepository;
    private $contactMethodRepository;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        WLTTeacherRepository $wltTeacherRepository,
        ProjectRepository $projectRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        ContactMethodRepository $contactMethodRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->projectRepository = $projectRepository;
        $this->userExtensionService = $userExtensionService;
        $this->wltStudentEnrollmentRepository = $wltStudentEnrollmentRepository;
        $this->contactMethodRepository = $contactMethodRepository;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    private function addElements(
        FormInterface $form,
        AcademicYear $academicYear = null,
        Workcenter $workcenter = null,
        $selectedProjects = [],
        $teachers = [],
        \DateTimeInterface $dateTime = null
    ) {
        $workcenters = $this->workcenterRepository->findAllSorted();
        $methods = [];

        if ($academicYear &&
            $academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            if (!$teachers) {
                $teachers = $this->wltTeacherRepository->findByAcademicYear($academicYear);
            }
            $methods = $this->contactMethodRepository->findEnabledByAcademicYear($academicYear);
        } else {
            $teachers = [];
        }
        $studentEnrollments = [];
        if ($workcenter !== null) {
            $projects = $this->projectRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
            if ((is_array($projects) || $projects instanceof \Countable ? count($projects) : 0) > 0) {
                $studentEnrollments =
                    $this
                        ->wltStudentEnrollmentRepository
                        ->findByWorkcenterProjectsAndAgreementDate($workcenter, $selectedProjects, $dateTime);
            }
        } else {
            $projects = [];
        }
        $canSelectProjects = (is_array($projects) || $projects instanceof \Countable ? count($projects) : 0) > 0;
        $canSelectStudentEnrollments =
            (
                is_array($studentEnrollments) || $studentEnrollments instanceof \Countable
                    ? count($studentEnrollments) : 0
            ) > 0;

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
            ->add('method', EntityType::class, [
                'label' => 'form.method',
                'class' => ContactMethod::class,
                'choices' => $methods,
                'placeholder' => 'form.method.on-site',
                'required' => false
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
            ->add('detail', CKEditorType::class, [
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
            'data_class' => Contact::class,
            'teachers' => [],
            'translation_domain' => 'wlt_contact'
        ]);
    }
}
