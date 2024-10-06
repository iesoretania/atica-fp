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

use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\ProgramGroup;
use App\Repository\Edu\TeacherRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ProgramGroupType extends AbstractType
{
    public function __construct(
        private readonly TeacherRepository $teacherRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetHours', MoneyType::class, [
                'label' => 'form.target_hours',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new GreaterThanOrEqual(0)
                ],
                'required' => false
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
            ]);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            $managers = $this->teacherRepository->findByGroup($data->getGroup());
            $form
                ->add('managers', EntityType::class, [
                    'label' => 'form.managers',
                    'class' => Teacher::class,
                    'choices' => $managers,
                    'expanded' => false,
                    'multiple' => true,
                    'required' => false
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgramGroup::class,
            'translation_domain' => 'itp_group'
        ]);
    }
}
