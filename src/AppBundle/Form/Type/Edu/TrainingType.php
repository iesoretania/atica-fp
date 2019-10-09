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

namespace AppBundle\Form\Type\Edu;

use AppBundle\Entity\Edu\Department;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Survey;
use AppBundle\Repository\Edu\DepartmentRepository;
use AppBundle\Repository\SurveyRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingType extends AbstractType
{
    /** @var DepartmentRepository */
    private $departmentRepository;

    /** @var UserExtensionService */
    private $userExtensionService;
    /**
     * @var SurveyRepository
     */
    private $surveyRepository;

    public function __construct(
        DepartmentRepository $departmentRepository,
        UserExtensionService $userExtensionService,
        SurveyRepository $surveyRepository
    ) {
        $this->departmentRepository = $departmentRepository;
        $this->userExtensionService = $userExtensionService;
        $this->surveyRepository = $surveyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        $departments = $this->departmentRepository->findByAcademicYear($academicYear);

        $surveys = $this->surveyRepository->findByOrganization($this->userExtensionService->getCurrentOrganization());

        $builder
            ->add('department', EntityType::class, [
                'label' => 'form.department',
                'class' => Department::class,
                'choice_translation_domain' => false,
                'choices' => $departments,
                'placeholder' => 'form.no_department',
                'required' => false
            ])
            ->add('name', null, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('internalCode', null, [
                'label' => 'form.internal_code',
                'required' => false,
                'disabled' => true
            ])
            ->add('wltStudentSurvey', EntityType::class, [
                'label' => 'form.wlt_student_survey',
                'class' => Survey::class,
                'choice_label' => 'title',
                'choice_translation_domain' => false,
                'choices' => $surveys,
                'placeholder' => 'form.no_survey',
                'required' => false
            ])
            ->add('wltCompanySurvey', EntityType::class, [
                'label' => 'form.wlt_company_survey',
                'class' => Survey::class,
                'choice_label' => 'title',
                'choice_translation_domain' => false,
                'choices' => $surveys,
                'placeholder' => 'form.no_survey',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Training::class,
            'translation_domain' => 'edu_training'
        ]);
    }
}
