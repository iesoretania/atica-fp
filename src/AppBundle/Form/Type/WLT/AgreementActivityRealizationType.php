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

namespace AppBundle\Form\Type\WLT;

use AppBundle\Entity\WLT\ActivityRealizationGrade;
use AppBundle\Entity\WLT\AgreementActivityRealization;
use AppBundle\Repository\WLT\ActivityRealizationGradeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AgreementActivityRealizationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var AgreementActivityRealization $data */
            $data = $event->getData();

            $form
                ->add('grade', EntityType::class, [
                    'label' => (string) $data->getActivityRealization(),
                    'choice_translation_domain' => false,
                    'class' => ActivityRealizationGrade::class,
                    'expanded' => true,
                    'label_attr' => ['class' => 'radio-inline'],
                    'placeholder' => $this->translator->trans(
                        'form.grade_none',
                        [],
                        'wlt_agreement_activity_realization'
                    ),
                    'query_builder' => function (ActivityRealizationGradeRepository $entityRepository) {
                        return $entityRepository->createQueryBuilder('arg')
                            ->orderBy('arg.numericGrade', 'ASC');
                    },
                    'required' => false
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AgreementActivityRealization::class,
            'translation_domain' => false
        ]);
    }
}
