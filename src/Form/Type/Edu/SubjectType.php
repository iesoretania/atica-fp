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

use App\Entity\Edu\Grade;
use App\Entity\Edu\Subject;
use App\Repository\Edu\GradeRepository;
use App\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubjectType extends AbstractType
{
    /** @var GradeRepository */
    private $gradeRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        GradeRepository $gradeRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->gradeRepository = $gradeRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        $grades = $this->gradeRepository->findByAcademicYear($academicYear);

        $builder
            ->add('grade', EntityType::class, [
                'label' => 'form.grade',
                'class' => Grade::class,
                'choice_translation_domain' => false,
                'choices' => $grades,
                'required' => true
            ])
            ->add('name', null, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('code', null, [
                'label' => 'form.code',
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Subject::class,
            'is_admin' => false,
            'translation_domain' => 'edu_subject'
        ]);
    }
}
