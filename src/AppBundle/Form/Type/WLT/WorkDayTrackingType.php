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

use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\WorkDay;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Security\WLT\AgreementVoter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class WorkDayTrackingType extends AbstractType
{
    /** @var ActivityRealizationRepository */
    private $activityRealizationRepository;

    /** @var Security */
    private $security;

    public function __construct(
        ActivityRealizationRepository $activityRealizationRepository,
        Security $security
    ) {
        $this->activityRealizationRepository = $activityRealizationRepository;
        $this->security = $security;
    }

    public function addElements(
        FormInterface $form,
        Agreement $agreement,
        WorkDay $workDay
    ) {
        $activityRealizations = $this->activityRealizationRepository->findByTrainingAndCompany(
            $agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining(),
            $agreement->getWorkcenter()->getCompany()
        );

        $locked = $workDay->isLocked();
        $absence = $workDay->isAbsence();

        $lockManager = $this->security->isGranted(AgreementVoter::LOCK, $agreement);
        $attendanceManager = $this->security->isGranted(AgreementVoter::ATTENDANCE, $agreement);


        $form
            ->add('activityRealizations', EntityType::class, [
                'label' => 'form.activity_realizations',
                'class' => ActivityRealization::class,
                'choice_translation_domain' => false,
                'choices' => $activityRealizations,
                'expanded' => true,
                'group_by' => 'activity',
                'multiple' => true,
                'required' => false,
                'disabled' => $absence || $locked
            ]);

        if ($lockManager) {
            $form
                ->add('locked', ChoiceType::class, [
                    'label' => 'form.locked',
                    'required' => true,
                    'expanded' => true,
                    'choices' => [
                        'form.locked.no' => false,
                        'form.locked.yes' => true
                    ]
                ]);
        }

        if ($attendanceManager) {
            $form
                ->add('absence', ChoiceType::class, [
                    'label' => 'form.work_day.attendance',
                    'required' => true,
                    'expanded' => true,
                    'choices' => [
                        'form.work_day.attendance.yes' => false,
                        'form.work_day.attendance.no' => true
                    ],
                    'disabled' => $locked
                ]);
        }

        $form
            ->add('startTime1', null, [
                'label' => 'form.start_time_1',
                'required' => false,
                'attr' => ['placeholder' => 'form.time.placeholder'],
                'disabled' => $absence || $locked
            ])
            ->add('endTime1', null, [
                'label' => 'form.end_time_1',
                'required' => false,
                'attr' => ['placeholder' => 'form.time.placeholder'],
                'disabled' => $absence || $locked
            ])
            ->add('startTime2', null, [
                'label' => 'form.start_time_2',
                'required' => false,
                'attr' => ['placeholder' => 'form.time.placeholder'],
                'disabled' => $absence || $locked
            ])
            ->add('endTime2', null, [
                'label' => 'form.end_time_2',
                'required' => false,
                'attr' => ['placeholder' => 'form.time.placeholder'],
                'disabled' => $absence || $locked
            ])
            ->add('notes', null, [
                'label' => 'form.notes',
                'required' => false,
                'disabled' => $locked
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $this->addElements($form, $data->getAgreement(), $data);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $this->addElements($form, $options['work_day']->getAgreement(), $options['work_day']);
        });
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WorkDay::class,
            'work_day' => null,
            'translation_domain' => 'calendar'
        ]);
    }
}