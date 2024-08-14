<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Form\Type\Edu;

use App\Entity\Edu\Department;
use App\Entity\Edu\Teacher;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartmentType extends AbstractType
{
    public function __construct(private readonly TeacherRepository $teacherRepository, private readonly UserExtensionService $userExtensionService)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'disabled' => !$options['is_admin']
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Department::class,
            'is_admin' => false,
            'translation_domain' => 'edu_department'
        ]);
    }
}
