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

namespace App\Form\Type\WltModule;

use App\Entity\WltModule\ActivityRealizationGrade;
use App\Entity\WltModule\AgreementActivityRealization;
use App\Repository\WltModule\ActivityRealizationGradeRepository;
use App\Repository\WltModule\AgreementActivityRealizationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class AgreementActivityRealizationType extends AbstractType
{

    private $grades;
    private $submittedAgreementActivityRealizations;

    public function __construct(private readonly TranslatorInterface $translator, private readonly ActivityRealizationGradeRepository $activityRealizationGradeRepository, private readonly AgreementActivityRealizationRepository $agreementActivityRealizationRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            /** @var AgreementActivityRealization $data */
            $data = $event->getData();

            if (null === $this->grades) {
                $this->grades = $this->activityRealizationGradeRepository->findByProject(
                    $data->getAgreement()->getProject()
                );
                $this->submittedAgreementActivityRealizations = $this->agreementActivityRealizationRepository
                    ->findSubmittedByAgreement($data->getAgreement());
            }
            $form
                ->add('grade', EntityType::class, [
                    'label' => false,
                    'choice_translation_domain' => false,
                    'choices' => $this->grades,
                    'class' => ActivityRealizationGrade::class,
                    'disabled' => $data->isDisabled() ||
                        !in_array($data, $this->submittedAgreementActivityRealizations, true),
                    'expanded' => true,
                    'label_attr' => ['class' => 'radio-inline'],
                    'placeholder' => $this->translator->trans(
                        'form.grade_none',
                        [],
                        'wlt_agreement_activity_realization'
                    ),
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
            'data_class' => AgreementActivityRealization::class,
            'translation_domain' => false
        ]);
    }
}