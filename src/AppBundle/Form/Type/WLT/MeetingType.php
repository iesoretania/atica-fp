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

use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WLT\Meeting;
use AppBundle\Entity\WLT\Project;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\WLTStudentEnrollmentRepository;
use AppBundle\Repository\WLT\WLTTeacherRepository;
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
use Symfony\Component\Validator\Constraints\Count;

class MeetingType extends AbstractType
{
    /**
     * @var ProjectRepository
     */
    private $projectRepository;
    /**
     * @var WLTStudentEnrollmentRepository
     */
    private $wltStudentEnrollmentRepository;
    /**
     * @var WLTTeacherRepository
     */
    private $wltTeacherRepository;
    /**
     * @var UserExtensionService
     */
    private $userExtensionService;

    public function __construct(
        ProjectRepository $projectRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        WLTTeacherRepository $wltTeacherRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->projectRepository = $projectRepository;
        $this->wltStudentEnrollmentRepository = $wltStudentEnrollmentRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->userExtensionService = $userExtensionService;
    }

    private function addElements(
        FormInterface $form,
        $createdByTeachers,
        \DateTime $dateTime = null,
        Project $project = null,
        $projects = []
    ) {
        if ($project &&
            $project->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            $studentEnrollments = $this->wltStudentEnrollmentRepository
                ->findByProjectAndDate($project, $dateTime);

            $teachers = $this->wltTeacherRepository->findByProject($project);
        } else {
            $studentEnrollments = [];
            $teachers = [];
        }

        $canSelectStudentEnrollments = count($studentEnrollments) > 0;

        $form
            ->add('dateTime', DateTimeType::class, [
                'label' => 'form.date',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'model_timezone' => 'UTC',
                'required' => true
            ])
            ->add('createdBy', EntityType::class, [
                'label' => 'form.created_by',
                'class' => Teacher::class,
                'choices' => $createdByTeachers,
                'required' => true
            ])
            ->add('project', EntityType::class, [
                'label' => 'form.project',
                'class' => Project::class,
                'choices' => $projects,
                'required' => true
            ])
            ->add('studentEnrollments', EntityType::class, [
                'label' => 'form.students',
                'class' => StudentEnrollment::class,
                'choices' => $studentEnrollments,
                'disabled' => !$canSelectStudentEnrollments,
                'expanded' => $canSelectStudentEnrollments,
                'mapped' => $canSelectStudentEnrollments,
                'multiple' => $canSelectStudentEnrollments,
                'placeholder' => 'form.student_enrollments.none',
                'required' => false
            ])
            ->add('teachers', EntityType::class, [
                'label' => 'form.teachers',
                'class' => Teacher::class,
                'choices' => $teachers,
                'constraints' => [
                    new Count(['min' => 1])
                ],
                'multiple' => true,
                'required' => true
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

            $project = $data->getProject();
            $this->addElements($form, $options['teachers'], $data->getDateTime(), $project, $options['projects']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            /** @var Project $project */
            $project = isset($data['project']) ?
                $this->projectRepository->find($data['project']) :
                null;

            $this->addElements(
                $form,
                $options['teachers'],
                date_create($data['dateTime']['date'] . ' ' . $data['dateTime']['time']),
                $project,
                $options['projects']
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Meeting::class,
            'projects' => [],
            'teachers' => [],
            'translation_domain' => 'wlt_meeting'
        ]);
    }
}
