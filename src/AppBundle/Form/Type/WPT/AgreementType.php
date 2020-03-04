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

use AppBundle\Entity\Company;
use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Person;
use AppBundle\Entity\Workcenter;
use AppBundle\Entity\WPT\Activity;
use AppBundle\Entity\WPT\Agreement;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Repository\CompanyRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WorkcenterRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class AgreementType extends AbstractType
{
    private $workcenterRepository;
    private $companyRepository;
    private $teacherRepository;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        CompanyRepository $companyRepository,
        TeacherRepository $teacherRepository
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->companyRepository = $companyRepository;
        $this->teacherRepository = $teacherRepository;
    }

    public function addElements(
        FormInterface $form,
        Shift $shift,
        $new,
        AcademicYear $academicYear,
        Company $company = null
    ) {
        $studentEnrollments = $shift ? $shift->getStudentEnrollments() : [];

        $workcenters = $company ?
            $this->workcenterRepository->findByCompany(
                $company
            ) : [];

        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);

        if ($shift) {
            $activities = $shift->getActivities();
        }

        if (!$new) {
            $form
                ->add('studentEnrollment', EntityType::class, [
                    'label' => 'form.student_enrollment',
                    'class' => StudentEnrollment::class,
                    'choice_translation_domain' => false,
                    'choices' => $studentEnrollments,
                    'placeholder' => 'form.student_enrollment.none',
                    'required' => true
                ]);
        } else {
            $form
                ->add('studentEnrollments', EntityType::class, [
                    'label' => 'form.student_enrollments',
                    'class' => StudentEnrollment::class,
                    'mapped' => false,
                    'choice_translation_domain' => false,
                    'choices' => $studentEnrollments,
                    'placeholder' => 'form.student_enrollment.none',
                    'constraints' => [
                        new Count(['min' => 1])
                    ],
                    'multiple' => true,
                    'expanded' => true,
                    'required' => true
                ]);
        }

        $form
            ->add('educationalTutor', EntityType::class, [
                'label' => 'form.educational_tutor',
                'class' => Teacher::class,
                'choices' => $teachers,
                'placeholder' => 'form.educational_tutor.none',
                'required' => true
            ])
            ->add('company', EntityType::class, [
                'label' => 'form.company',
                'mapped' => false,
                'class' => Company::class,
                'choice_label' => 'fullName',
                'choice_translation_domain' => false,
                'data' => $company,
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name');
                },
                'placeholder' => 'form.company.none',
                'required' => true
            ])
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choice_translation_domain' => false,
                'choice_label' => 'name',
                'choices' => $workcenters,
                'placeholder' => 'form.workcenter.none',
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
                'required' => false
            ])
            ->add('signDate', null, [
                'label' => 'form.sign_date',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('startDate', null, [
                'label' => 'form.start_date',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('endDate', null, [
                'label' => 'form.end_date',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('defaultStartTime1', null, [
                'label' => 'form.default_start_time_1',
                'required' => false
            ])
            ->add('defaultEndTime1', null, [
                'label' => 'form.default_end_time_1',
                'required' => false
            ])
            ->add('defaultStartTime2', null, [
                'label' => 'form.default_start_time_2',
                'required' => false
            ])
            ->add('defaultEndTime2', null, [
                'label' => 'form.default_end_time_2',
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            $shift = $data->getShift();

            $company = $data->getWorkcenter() ? $data->getWorkcenter()->getCompany() : null;

            $this->addElements($form, $shift, $options['new'], $options['academic_year'], $company);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            /** @var Company|null $company */
            $company = isset($data['company']) ? $this->companyRepository->find($data['company']) : null;

            /** @var Shift $shift */
            $shift = $form->getData()->getShift();

            $this->addElements($form, $shift, $options['new'], $options['academic_year'], $company);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Agreement::class,
            'new' => false,
            'academic_year' => null,
            'translation_domain' => 'wpt_agreement'
        ]);
    }
}
