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

namespace AppBundle\Form\Type\WPT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Person;
use AppBundle\Entity\WPT\Activity;
use AppBundle\Entity\WPT\AgreementEnrollment;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Repository\CompanyRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WorkcenterRepository;
use AppBundle\Repository\WPT\WPTStudentEnrollmentRepository;
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
    private $workcenterRepository;
    private $companyRepository;
    private $teacherRepository;
    private $WPTStudentEnrollmentRepository;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        CompanyRepository $companyRepository,
        TeacherRepository $teacherRepository,
        WPTStudentEnrollmentRepository $WPTStudentEnrollmentRepository
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->companyRepository = $companyRepository;
        $this->teacherRepository = $teacherRepository;
        $this->WPTStudentEnrollmentRepository = $WPTStudentEnrollmentRepository;
    }

    public function addElements(
        FormInterface $form,
        Shift $shift,
        AcademicYear $academicYear
    ) {
        $studentEnrollments = $shift ? $this->WPTStudentEnrollmentRepository->findByShift($shift) : [];
        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);

        if ($shift) {
            $activities = $shift->getActivities();
        }

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
            ->add('workTutor', Select2EntityType::class, [
                'label' => 'form.work_tutor',
                'multiple' => false,
                'text_property' => 'fullDisplayName',
                'class' => Person::class,
                'minimum_input_length' => 9,
                'remote_route' => 'api_person_query',
                'placeholder' => 'form.work_tutor.none',
                'attr' => ['class' => 'person'],
                'required' => true
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            $shift = $data->getAgreement()->getShift();

            $this->addElements($form, $shift, $options['academic_year']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();

            /** @var Shift $shift */
            $shift = $form->getData()->getAgreement()->getShift();

            $this->addElements($form, $shift, $options['academic_year']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AgreementEnrollment::class,
            'academic_year' => null,
            'translation_domain' => 'wpt_agreement'
        ]);
    }
}
