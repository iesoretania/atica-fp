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
use App\Entity\Edu\StudentEnrollment;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\Workcenter;
use App\Repository\Edu\StudentEnrollmentRepository;
use App\Repository\ItpModule\CompanyRepository;
use App\Repository\WorkcenterRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentProgramType extends AbstractType
{
    public function __construct(
        private readonly StudentEnrollmentRepository $studentEnrollmentRepository,
        private readonly CompanyRepository           $itpCompanyRepository,
        private readonly WorkcenterRepository        $workcenterRepository,
        private readonly CompanyRepository $companyRepository
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
            assert($data instanceof StudentProgram);
            assert($data->getProgramGroup() instanceof ProgramGroup);
            assert($data->getProgramGroup()->getProgramGrade() instanceof ProgramGrade);

            if ($data->getId() === null || !$data->getStudentEnrollment() instanceof StudentEnrollment) {
                $students = $this->studentEnrollmentRepository->findByGroup($data->getProgramGroup()->getGroup());
            } else {
                $students = [$data->getStudentEnrollment()];
            }

            $companies = $this->itpCompanyRepository->findByProgramGrade($data->getProgramGroup()->getProgramGrade());

            if ($form->has('company') && $form->get('company')->getData() instanceof Company) {
                $workcenters = $this->workcenterRepository->findByCompany($form->get('company')->getData());
            } elseif ($data->getWorkcenter() instanceof Workcenter) {
                $workcenters = $this->workcenterRepository->findByCompany($data->getWorkcenter()->getCompany());
            } else {
                $workcenters = [];
            }
            $this->addElements($form, $students, $companies, $workcenters);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $formData = $event->getData();
            $data = $form->getData();
            $students = $this->studentEnrollmentRepository->findByGroup($data->getProgramGroup()->getGroup());
            $company = $this->companyRepository->find($formData['company']);
            $companies = $this->itpCompanyRepository->findByProgramGrade($data->getProgramGroup()->getProgramGrade());

            if ($company instanceof Company) {
                $workcenters = $this->workcenterRepository->findByCompany($company);
            } else {
                $workcenters = [];
            }

            $this->addElements($form, $students, $companies, $workcenters);
        });
    }

    private function addElements(FormInterface $form, array $students, array $companies, array $workcenters): void
    {
        $form
            ->add('studentEnrollment', EntityType::class, [
                'label' => 'form.student',
                'class' => StudentEnrollment::class,
                'choices' => $students,
                'choice_label' => function (StudentEnrollment $studentEnrollment) {
                    return $studentEnrollment->getPerson()->getLastName() . ', ' . $studentEnrollment->getPerson()->getFirstName();
                },
                'placeholder' => count($students) === 1 ? 'form.fixed_student' : 'form.no_student',
                'required' => true
            ])
            ->add('modality', ChoiceType::class, [
                'label' => 'form.modality',
                'choices' => [
                    'form.modality.inherited' => ProgramGroup::MODE_INHERITED,
                    'form.modality.general' => ProgramGroup::MODE_GENERAL,
                    'form.modality.intensive' => ProgramGroup::MODE_INTENSIVE
                ],
                'expanded' => true,
                'required' => true
            ])
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
            ->add('authorizationNeeded', ChoiceType::class, [
                'label' => 'form.authorization_needed',
                'choices' => [
                    'form.authorization_needed.no' => false,
                    'form.authorization_needed.yes' => true
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('authorizationDescription', TextareaType::class, [
                'label' => 'form.authorization_description',
                'attr' => ['rows' => 3],
                'required' => false
            ])
            ->add('adaptationNeeded', ChoiceType::class, [
                'label' => 'form.adaptation_needed',
                'choices' => [
                    'form.adaptation_needed.no' => false,
                    'form.adaptation_needed.yes' => true
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('adaptationDescription', TextareaType::class, [
                'label' => 'form.adaptation_description',
                'attr' => ['rows' => 3],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StudentProgram::class,
            'constraints' => [
                new UniqueEntity(['fields' => ['studentEnrollment', 'workcenter'], 'message' => 'student_program.enrollment.workcenter.unique'])
            ],
            'translation_domain' => 'itp_student_program'
        ]);
    }
}
