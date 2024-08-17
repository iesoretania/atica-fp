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

use App\Entity\Edu\AcademicYear;
use App\Entity\Workcenter;
use App\Form\Model\WltModule\ContactWorkcenterReport;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WltModule\ProjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactWorkcenterReportType extends AbstractType
{
    public function __construct(private readonly ProjectRepository $projectRepository, private readonly ContactMethodRepository $contactMethodRepository, private readonly TranslatorInterface $translator)
    {
    }

    private function addElements(
        FormInterface $form,
        Workcenter $workcenter,
        AcademicYear $academicYear
    ): void {
        $methods = $this->contactMethodRepository->findEnabledByAcademicYear($academicYear);
        $methodsChoices = [
            null,
        ];
        foreach ($methods as $method) {
            $methodsChoices[] = $method;
        }

        $projects = [null];
        $potentialProjects = $this->projectRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
        foreach ($potentialProjects as $project) {
            $projects[] = $project;
        }

        $form
            ->add('projects', ChoiceType::class, [
                'label' => 'form.projects.to_report',
                'choices' => $projects,
                'choice_translation_domain' => false,
                'choice_label' => function ($p) {
                    if ($p === null) {
                        return $this->translator->trans('form.projects.not_specified', [], 'wlt_contact');
                    }
                    return $p->__toString();
                },
                'choice_value' => function ($m) {
                    if ($m === null) {
                        return 0;
                    }
                    return $m->getId();
                },
                'expanded' => true,
                'multiple' => true,
                'placeholder' => 'form.projects.all',
                'required' => true
            ])
            ->add('contactMethods', ChoiceType::class, [
                'label' => 'form.methods',
                'choices' => $methodsChoices,
                'choice_translation_domain' => false,
                'choice_label' => function ($m) {
                    if ($m === null) {
                        return $this->translator->trans('form.method.on-site', [], 'wlt_contact');
                    }
                    return $m->getDescription();
                },
                'choice_value' => function ($m) {
                    if ($m === null) {
                        return 0;
                    }
                    return $m->getId();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();

            $this->addElements(
                $form,
                $options['workcenter'],
                $options['academic_year']
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();

            $this->addElements(
                $form,
                $options['workcenter'],
                $options['academic_year']
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactWorkcenterReport::class,
            'workcenter' => null,
            'academic_year' => null,
            'translation_domain' => 'wlt_contact'
        ]);
    }
}