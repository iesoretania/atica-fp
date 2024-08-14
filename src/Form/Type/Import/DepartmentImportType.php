<?php
/*
  ÁTICA - Aplicación web para la gestión documental de centros educativos

  Copyright (C) 2015-2017: Luis Ramón López López

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

namespace App\Form\Type\Import;

use App\Entity\Edu\AcademicYear;
use App\Form\Model\DepartmentImport;
use App\Repository\Edu\AcademicYearRepository;
use App\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartmentImportType extends AbstractType
{
    /**
     * DepartmentImportType constructor.
     */
    public function __construct(private readonly AcademicYearRepository $academicYearRepository, private readonly UserExtensionService $userExtensionService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $academicYears = $this->academicYearRepository->findAllByOrganization(
            $this->userExtensionService->getCurrentOrganization()
        );
        $builder
            ->add('academicYear', EntityType::class, [
                'label' => 'form.group.academic_year',
                'class' => AcademicYear::class,
                'choice_translation_domain' => false,
                'choices' => $academicYears,
                'required' => true
            ])
            ->add('file', FileType::class, [
                'label' => 'form.file',
                'required' => true
            ])
            ->add('extractHeads', ChoiceType::class, [
                'label' => 'form.department.extract_heads',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.department.extract_heads.yes' => true,
                    'form.department.extract_heads.no' => false
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DepartmentImport::class,
            'organization' => null,
            'translation_domain' => 'import'
        ]);
    }
}
