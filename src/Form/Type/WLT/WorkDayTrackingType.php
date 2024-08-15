<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

use App\Entity\WLT\ActivityRealization;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\WorkDay;
use App\Security\WLT\AgreementVoter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkDayTrackingType extends AbstractType
{
    public function __construct(private readonly Security $security, private readonly TranslatorInterface $translator)
    {
    }

    public function addElements(
        FormInterface $form,
        Agreement $agreement,
        WorkDay $workDay,
        $lockedActivityRealizations
    ): void {
        $activityRealizations = $agreement->getActivityRealizations();

        $locked = $workDay->isLocked();
        $absence = $workDay->getAbsence() !== WorkDay::NO_ABSENCE;

        $lockManager = $this->security->isGranted(AgreementVoter::LOCK, $agreement);
        $attendanceManager = $this->security->isGranted(AgreementVoter::ATTENDANCE, $agreement);


        $form
            ->add('activityRealizations', EntityType::class, [
                'label' => 'form.activity_realizations',
                'class' => ActivityRealization::class,
                'choice_attr' => fn(ActivityRealization $ar): array => (!$lockManager && in_array($ar, $lockedActivityRealizations, true)) ?
                    ['disabled' => 'disabled'] :
                    [],
                'choice_label' => function (ActivityRealization $ar) use ($lockedActivityRealizations, $lockManager): string {
                    $label = $ar->__toString();
                    if (in_array($ar, $lockedActivityRealizations, true)) {
                        if ($lockManager) {
                            $label = '***' . $label;
                        }
                        $label .= $this->translator->trans('form.caption.locked', [], 'wlt_tracking');
                    }
                    return $label;
                },
                'choice_translation_domain' => false,
                'choices' => $activityRealizations,
                'expanded' => true,
                'group_by' => 'activity',
                'multiple' => true,
                'required' => false,
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
                        'form.work_day.attendance.no_absence' => WorkDay::NO_ABSENCE,
                        'form.work_day.attendance.unjustified_absence' => WorkDay::UNJUSTIFIED_ABSENCE,
                        'form.work_day.attendance.justified_absence' => WorkDay::JUSTIFIED_ABSENCE
                    ] : [
                        'form.work_day.attendance.no_absence' => WorkDay::NO_ABSENCE,
                        'form.work_day.attendance.unjustified_absence' => WorkDay::UNJUSTIFIED_ABSENCE
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();

            $this->addElements($form, $data->getAgreement(), $data, $options['locked_activity_realizations']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $this->addElements(
                $form,
                $options['work_day']->getAgreement(),
                $options['work_day'],
                $options['locked_activity_realizations']
            );
        });
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkDay::class,
            'work_day' => null,
            'locked_activity_realizations' => [],
            'translation_domain' => 'calendar'
        ]);
    }
}
