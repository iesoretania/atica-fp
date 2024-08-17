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

namespace App\Form\Type\WptModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WptModule\Activity;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\StudentEnrollmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class AgreementEnrollmentType extends AbstractType
{
    public function __construct(private readonly TeacherRepository $teacherRepository, private readonly StudentEnrollmentRepository $WPTStudentEnrollmentRepository)
    {
    }

    public function addElements(
        FormInterface $form,
        Shift $shift,
        AcademicYear $academicYear
    ): void {
        $studentEnrollments = $this->WPTStudentEnrollmentRepository->findByShift($shift);
        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);

        $activities = $shift->getActivities();

        $form
            ->add('studentEnrollment', EntityType::class, [
                'label' => 'form.student_enrollment',
                'class' => StudentEnrollment::class,
                'choice_translation_domain' => false,
                'choices' => $studentEnrollments,
                'placeholder' => 'form.student_enrollment.none',
                'required' => true
            ])
            ->add('educationalTutor', EntityType::class, [
                'label' => 'form.educational_tutor',
                'class' => Teacher::class,
                'choices' => $teachers,
                'placeholder' => 'form.educational_tutor.none',
                'required' => true
            ])
            ->add('additionalEducationalTutor', EntityType::class, [
                'label' => 'form.additional_educational_tutor',
                'class' => Teacher::class,
                'choices' => $teachers,
                'placeholder' => 'form.additional_educational_tutor.none',
                'required' => false
            ])
            ->add('workTutor', Select2EntityType::class, [
                'label' => 'form.work_tutor',
                'multiple' => false,
                'text_property' => 'fullDisplayName',
                'class' => Person::class,
                'minimum_input_length' => 3,
                'remote_route' => 'api_person_query',
                'placeholder' => 'form.work_tutor.none',
                'attr' => ['class' => 'person'],
                'required' => true
            ])
            ->add('additionalWorkTutor', Select2EntityType::class, [
                'label' => 'form.additional_work_tutor',
                'multiple' => false,
                'text_property' => 'fullDisplayName',
                'class' => Person::class,
                'minimum_input_length' => 3,
                'remote_route' => 'api_person_query',
                'placeholder' => 'form.additional_work_tutor.none',
                'attr' => ['class' => 'person'],
                'required' => false
            ])
            ->add('activities', EntityType::class, [
                'label' => 'form.activities',
                'class' => Activity::class,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'choices' => $activities
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

            $shift = $data->getAgreement()->getShift();

            $this->addElements($form, $shift, $options['academic_year']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();

            /** @var Shift $shift */
            $shift = $form->getData()->getAgreement()->getShift();

            $this->addElements($form, $shift, $options['academic_year']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgreementEnrollment::class,
            'academic_year' => null,
            'translation_domain' => 'wpt_agreement'
        ]);
    }
}
