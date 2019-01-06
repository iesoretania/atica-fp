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
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartmentType extends AbstractType
{
    /** @var AcademicYearRepository */
    private $academicYearRepository;

    /** @var TeacherRepository */
    private $teacherRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        AcademicYearRepository $academicYearRepository,
        TeacherRepository $teacherRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->academicYearRepository = $academicYearRepository;
        $this->teacherRepository = $teacherRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);

        $builder
            ->add('name', null, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('head', EntityType::class, [
                'label' => 'form.head',
                'class' => Teacher::class,
                'choice_translation_domain' => false,
                'choices' => $teachers,
                'placeholder' => 'form.no_head',
                'required' => false
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
            'data_class' => Department::class,
            'translation_domain' => 'edu_department'
        ]);
    }
}
