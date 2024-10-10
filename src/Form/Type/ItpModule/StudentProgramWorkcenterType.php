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

namespace App\Form\Type\ItpModule;

use App\Entity\Company;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Repository\Edu\TeacherRepository;
use App\Repository\ItpModule\CompanyRepository;
use App\Repository\WorkcenterRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class StudentProgramWorkcenterType extends AbstractType
{
    public function __construct(
        private readonly CompanyRepository    $itpCompanyRepository,
        private readonly WorkcenterRepository $workcenterRepository,
        private readonly CompanyRepository    $companyRepository, private readonly TeacherRepository $teacherRepository
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();
            assert($data instanceof StudentProgramWorkcenter);
            $studentProgram = $data->getStudentProgram();
            assert($studentProgram instanceof StudentProgram);
            assert($studentProgram->getProgramGroup() instanceof ProgramGroup);
            assert($studentProgram->getProgramGroup()->getProgramGrade() instanceof ProgramGrade);

            $companies = $this->itpCompanyRepository->findByProgramGrade($studentProgram->getProgramGroup()->getProgramGrade());
            $teachers = $this->teacherRepository->findByGroup($studentProgram->getProgramGroup()->getGroup());

            if ($form->has('company') && $form->get('company')->getData() instanceof Company) {
                $workcenters = $this->workcenterRepository->findByCompany($form->get('company')->getData());
            } elseif ($data->getWorkcenter() instanceof Workcenter) {
                $workcenters = $this->workcenterRepository->findByCompany($data->getWorkcenter()->getCompany());
            } else {
                $workcenters = [];
            }
            $this->addElements($form, $companies, $workcenters, $teachers);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $formData = $event->getData();
            $data = $form->getData();
            $company = $this->companyRepository->find($formData['company']);
            $companies = $this->itpCompanyRepository->findByProgramGrade($data->getStudentProgram()->getProgramGroup()->getProgramGrade());
            $teachers = $this->teacherRepository->findByGroup($data->getStudentProgram()->getProgramGroup()->getGroup());

            if ($company instanceof Company) {
                $workcenters = $this->workcenterRepository->findByCompany($company);
            } else {
                $workcenters = [];
            }

            $this->addElements($form, $companies, $workcenters, $teachers);
        });
    }

    private function addElements(FormInterface $form, array $companies, array $workcenters, array $teachers): void
    {
        $form
            ->add('company', EntityType::class, [
                'mapped' => false,
                'class' => Company::class,
                'label' => 'form.company',
                'choices' => $companies,
                'choice_label' => function (Company $company) {
                    return $company->getCode() . ' - ' . $company->getName();
                },
                'choice_translation_domain' => false,
                'placeholder' => 'form.no_company',
                'required' => true
            ])
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choices' => $workcenters,
                'choice_label' => 'name',
                'placeholder' => 'form.no_workcenter',
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
            ->add('startDate', DateType::class, [
                'label' => 'form.start_date',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('endDate', DateType::class, [
                'label' => 'form.end_date',
                'widget' => 'single_text',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StudentProgramWorkcenter::class,
            'constraints' => [
                new UniqueEntity(['fields' => ['studentProgram', 'workcenter'], 'message' => 'student_program.enrollment.workcenter.unique'])
            ],
            'translation_domain' => 'itp_student_program_workcenter'
        ]);
    }
}
