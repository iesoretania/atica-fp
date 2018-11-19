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

use AppBundle\Entity\Edu\Grade;
use AppBundle\Entity\Edu\Group;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\GradeRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    /** @var AcademicYearRepository */
    private $academicYearRepository;

    /** @var GradeRepository */
    private $gradeRepository;

    /** @var UserExtensionService */
    private $userExtensionService;


    public function __construct(
        GradeRepository $gradeRepository,
        AcademicYearRepository $academicYearRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->gradeRepository = $gradeRepository;
        $this->academicYearRepository = $academicYearRepository;
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

        $grades = $this->gradeRepository->findByAcademicYear($academicYear);

        $builder
            ->add('grade', EntityType::class, [
                'label' => 'form.grade',
                'class' => Grade::class,
                'choices' => $grades,
                'required' => true
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
            'data_class' => Group::class,
            'translation_domain' => 'edu_group'
        ]);
    }
}
