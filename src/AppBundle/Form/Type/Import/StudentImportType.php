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

namespace AppBundle\Form\Type\Import;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Form\Model\StudentImport;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentImportType extends AbstractType
{
    /** @var AcademicYearRepository */
    private $academicYearRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    /**
     * DepartmentImportType constructor.
     * @param AcademicYearRepository $academicYearRepository
     * @param UserExtensionService $userExtensionService
     */
    public function __construct(
        AcademicYearRepository $academicYearRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->academicYearRepository = $academicYearRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StudentImport::class,
            'organization' => null,
            'translation_domain' => 'import'
        ]);
    }
}
