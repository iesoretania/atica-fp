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

namespace AppBundle\Form\Type\WLT;

use AppBundle\Entity\Company;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Person;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\Project;
use AppBundle\Entity\Workcenter;
use AppBundle\Repository\Edu\StudentEnrollmentRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Repository\WLT\WLTCompanyRepository;
use AppBundle\Repository\WorkcenterRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class AgreementType extends AbstractType
{
    private $studentEnrollmentRepository;
    private $workcenterRepository;
    private $wltCompanyRepository;
    private $activityRealizationRepository;
    private $teacherRepository;

    public function __construct(
        StudentEnrollmentRepository $studentEnrollmentRepository,
        WorkcenterRepository $workcenterRepository,
        WLTCompanyRepository $wltCompanyRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        TeacherRepository $teacherRepository
    ) {
        $this->studentEnrollmentRepository = $studentEnrollmentRepository;
        $this->workcenterRepository = $workcenterRepository;
        $this->wltCompanyRepository = $wltCompanyRepository;
        $this->activityRealizationRepository = $activityRealizationRepository;
        $this->teacherRepository = $teacherRepository;
    }

    public function addElements(
        FormInterface $form,
        Project $project,
        Company $company = null,
        StudentEnrollment $studentEnrollment = null,
        $currentActivityRealizations = []
    ) {
        $studentEnrollments = $project ? $project->getStudentEnrollments() : [];

        $workcenters = ($studentEnrollment && $company) ?
            $this->workcenterRepository->findByCompany(
                $company
            ) : [];

        $teachers = $studentEnrollment ?
            $this->teacherRepository->findByAcademicYear(
                $studentEnrollment->getGroup()->getGrade()->getTraining()->getAcademicYear()
            ) : [];

        if ($studentEnrollment) {
            if ($company) {
                $activityRealizations = $this->activityRealizationRepository->
                    findByProjectAndCompany($project, $company);
            } else {
                $activityRealizations = [];
            }
            $companies = $this->wltCompanyRepository->findByLearningProgramFromProject($project);
        } else {
            $activityRealizations = [];
            $companies = [];
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
            ->add('company', EntityType::class, [
                'label' => 'form.company',
                'mapped' => false,
                'class' => Company::class,
                'choice_label' => 'fullName',
                'choice_translation_domain' => false,
                'choices' => $companies,
                'data' => $company,
                'query_builder' => function (EntityRepository $er) {
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
            ->add('activityRealizations', EntityType::class, [
                'label' => 'form.activity_realizations',
                'mapped' => false,
                'class' => ActivityRealization::class,
                'data' => $currentActivityRealizations,
                'expanded' => true,
                'group_by' => function (ActivityRealization $ar) {
                    return (string) $ar->getActivity();
                },
                'multiple' => true,
                'required' => false,
                'choices' => $activityRealizations
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

            $project = $data->getProject();

            $company = $data->getWorkcenter() ? $data->getWorkcenter()->getCompany() : null;

            $studentEnrollment = $data->getStudentEnrollment();

            $currentActivityRealizations = $data->getActivityRealizations();

            $this->addElements($form, $project, $company, $studentEnrollment, $currentActivityRealizations);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            /** @var Company|null $company */
            $company = isset($data['company']) ? $this->wltCompanyRepository->find($data['company']) : null;

            /** @var Project $project */
            $project = $form->getData()->getProject();

            /** @var StudentEnrollment $studentEnrollment */
            $studentEnrollment = isset($data['studentEnrollment']) ?
                $this->studentEnrollmentRepository->find($data['studentEnrollment']) :
                null;

            $this->addElements($form, $project, $company, $studentEnrollment, null);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Agreement::class,
            'translation_domain' => 'wlt_agreement'
        ]);
    }
}
