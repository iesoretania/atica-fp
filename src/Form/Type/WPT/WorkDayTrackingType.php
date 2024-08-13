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

namespace App\Form\Type\WPT;

use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\TrackedWorkDay;
use App\Security\WPT\AgreementEnrollmentVoter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class WorkDayTrackingType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function addElements(
        FormInterface $form,
        AgreementEnrollment $agreementEnrollment,
        TrackedWorkDay $workDay
    ) {
        $locked = $workDay->isLocked();
        $absence = $workDay->getAbsence() !== TrackedWorkDay::NO_ABSENCE;

        $lockManager = $this->security->isGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);
        $attendanceManager = $this->security->isGranted(AgreementEnrollmentVoter::ATTENDANCE, $agreementEnrollment);

        $form
            ->add('trackedActivities', CollectionType::class, [
                'entry_type' => ActivityTrackingType::class,
                'required' => false,
                'label' => false,
                'entry_options' => [
                    'label' => false
                ],
                'disabled' => $absence || $locked
            ])
            ->add('otherActivities', null, [
                'label' => 'form.other_activities',
                'required' => false,
                'disabled' => $locked
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
                    'choices' => $lockManager ? [
                        'form.work_day.attendance.no_absence' => 0,
                        'form.work_day.attendance.unjustified_absence' => 1,
                        'form.work_day.attendance.justified_absence' => 2
                    ] : [
                        'form.work_day.attendance.no_absence' => 0,
                        'form.work_day.attendance.unjustified_absence' => 1
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
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            $this->addElements($form, $data->getAgreementEnrollment(), $data);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $this->addElements(
                $form,
                $options['work_day']->getAgreementEnrollment(),
                $options['work_day']
            );
        });
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TrackedWorkDay::class,
            'work_day' => null,
            'translation_domain' => 'calendar'
        ]);
    }
}
