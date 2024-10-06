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

use App\Entity\Company;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\ProgramGrade;
use App\Repository\ItpModule\CompanyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanyProgramType extends AbstractType
{
    public function __construct(
        private readonly CompanyRepository $itpCompanyRepository,
        private readonly TranslatorInterface $translator
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();
            assert($data instanceof CompanyProgram);
            assert($data->getProgramGrade() instanceof ProgramGrade);

            if ($data->getId() === null) {
                $companies = $this->itpCompanyRepository->findAllButInProgramGrade($data->getProgramGrade());
            } else {
                $companies = [$data->getCompany()];
            }

            $form
                ->add('company', EntityType::class, [
                    'label' => 'form.company',
                    'class' => Company::class,
                    'choice_label' => function (Company $company) {
                        return $company->getCode() . ' - ' . $company->getName();
                    },
                    'placeholder' => 'form.no_company',
                    'choices' => $companies,
                    'disabled' => count($companies) === 1,
                    'required' => true
                ])
                ->add('agreementNumber', TextType::class, [
                    'label' => 'form.agreement_number',
                    'required' => false
                ])
                ->add('monitoringInstruments', TextareaType::class, [
                    'label' => 'form.monitoring_instruments',
                    'attr' => [
                        'placeholder' => $this->translator->trans('form.monitoring_instruments.placeholder', [], 'itp_company'),
                        'rows' => 5
                    ],
                    'required' => false
                ])
                ->add('programActivities', EntityType::class, [
                    'label' => 'form.program_activities',
                    'class' => Activity::class,
                    'choice_label' => function ($activity) {
                        return $activity->getCode() . ' - ' . $activity->getName();
                    },
                    'constraints' => [
                        new Count(['min' => 1, 'minMessage' => 'selection.count.invalid.min'])
                    ],
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'query_builder' => function ($er) use ($data) {
                        return $er->createQueryBuilder('a')
                            ->join('a.programGrade', 'pg')
                            ->where('pg = :programGrade')
                            ->addOrderBy('a.code', 'ASC')
                            ->addOrderBy('a.name', 'ASC')
                            ->setParameter('programGrade', $data->getProgramGrade());
                    }
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CompanyProgram::class,
            'constraints' => [
                new UniqueEntity(['fields' => ['company', 'programGrade']])
            ],
            'translation_domain' => 'itp_company'
        ]);
    }
}
