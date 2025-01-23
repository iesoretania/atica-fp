<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Form\Type\ItpModule;

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use App\Security\ItpModule\StudentProgramWorkcenterVoter;
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
    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function addElements(
        FormInterface            $form,
        StudentProgramWorkcenter $studentProgramWorkcenter,
        WorkDay                  $workDay,
        array                    $lockedActivities
    ): void {
        $activities = [];
        foreach ($studentProgramWorkcenter->getActivities() as $studentWorkcenterActivity) {
            $activity = $studentWorkcenterActivity->getActivity();
            $activities[$activity->getCode()] = $activity;
        }
        ksort($activities, SORT_NATURAL);

        $locked = $workDay->isLocked();
        $absence = $workDay->getAbsence() !== WorkDay::ABSENCE_NONE;

        $lockManager = $this->security->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter);
        $attendanceManager = $this->security->isGranted(StudentProgramWorkcenterVoter::ATTENDANCE, $studentProgramWorkcenter);

        $form
            ->add('activities', EntityType::class, [
                'label' => 'form.activities',
                'class' => Activity::class,
                'choice_attr' => fn(Activity $a): array => (!$lockManager && in_array($a, $lockedActivities, true)) ?
                    ['disabled' => 'disabled'] :
                    [],
                'choice_label' => function (Activity $a) use ($lockedActivities, $lockManager): string {
                    $label = $a->__toString();
                    if (in_array($a, $lockedActivities, true)) {
                        if ($lockManager) {
                            $label = $this->translator->trans('form.caption.locked_prefix', [], 'itp_tracking') . $label;
                        }
                        $label .= $this->translator->trans('form.caption.locked', [], 'itp_tracking');
                    }
                    return $label;
                },
                'choice_translation_domain' => false,
                'choices' => $activities,
                'expanded' => true,
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
                        'form.work_day.attendance.no_absence' => WorkDay::ABSENCE_NONE,
                        'form.work_day.attendance.unjustified_absence' => WorkDay::ABSENCE_UNJUSTIFIED,
                        'form.work_day.attendance.justified_absence' => WorkDay::ABSENCE_JUSTIFIED
                    ] : [
                        'form.work_day.attendance.no_absence' => WorkDay::ABSENCE_NONE,
                        'form.work_day.attendance.unjustified_absence' => WorkDay::ABSENCE_UNJUSTIFIED
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
            assert($data instanceof WorkDay);
            $this->addElements($form, $data->getStudentProgramWorkcenter(), $data, $options['locked_activities']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();
            $this->addElements(
                $form,
                $form->getData()->getStudentProgramWorkcenter(),
                $form->getData(),
                $options['locked_activities']
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
            'locked_activities' => [],
            'translation_domain' => 'itp_tracking'
        ]);
    }
}
