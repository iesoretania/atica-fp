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
use AppBundle\Entity\Edu\Training;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GradeType extends AbstractType
{
    /** @var TrainingRepository */
    private $trainingRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        TrainingRepository $trainingRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->trainingRepository = $trainingRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        $training = $this->trainingRepository->findByAcademicYear($academicYear);

        $builder
            ->add('training', EntityType::class, [
                'label' => 'form.grade',
                'class' => Training::class,
                'choices' => $training,
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
            'data_class' => Grade::class,
            'translation_domain' => 'edu_grade'
        ]);
    }
}
