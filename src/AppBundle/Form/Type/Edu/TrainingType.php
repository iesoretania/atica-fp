<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\DepartmentRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainingType extends AbstractType
{
    /** @var AcademicYearRepository */
    private $academicYearRepository;

    /** @var DepartmentRepository */
    private $departmentRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        AcademicYearRepository $academicYearRepository,
        DepartmentRepository $departmentRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->academicYearRepository = $academicYearRepository;
        $this->departmentRepository = $departmentRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $academicYear = $this->academicYearRepository->getCurrentByOrganization(
            $this->userExtensionService->getCurrentOrganization()
        );

        $departments = $this->departmentRepository->findByAcademicYear($academicYear);

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
