<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

namespace App\Form\Type\WLT;

use App\Entity\Edu\Teacher;
use App\Entity\Workcenter;
use App\Form\Model\WLT\ContactEducationalTutorReport;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WLT\ContactRepository;
use App\Repository\WLT\ProjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactEducationalTutorReportType extends AbstractType
{
    private $projectRepository;
    private $contactMethodRepository;
    private $contactRepository;
    private $translator;

    public function __construct(
        ProjectRepository $projectRepository,
        ContactRepository $contactRepository,
        ContactMethodRepository $contactMethodRepository,
        TranslatorInterface $translator
    ) {
        $this->projectRepository = $projectRepository;
        $this->contactRepository = $contactRepository;
        $this->contactMethodRepository = $contactMethodRepository;
        $this->translator = $translator;
    }

    private function addElements(
        FormInterface $form,
        Teacher $teacher,
        array $selectedProjects = []
    ) {
        $workcenters = $this->contactRepository->findWorkcentersByTeacherAndProjects($teacher, $selectedProjects);

        $academicYear = $teacher->getAcademicYear();
        $methods = $this->contactMethodRepository->findEnabledByAcademicYear($academicYear);
        $methodsChoices = [
            null,
        ];
        foreach ($methods as $method) {
            $methodsChoices[] = $method;
        }

        $projects = [null];
        $potentialProjects = $this->projectRepository->findByEducationalTutor($teacher);
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
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choices' => $workcenters,
                'placeholder' => 'form.workcenter.all',
                'required' => false
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            $this->addElements(
                $form,
                $options['teacher'],
                $data->getProjects()
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            if (isset($data['projects'])) {
                $selectedProjects = $this->projectRepository->findByIds($data['projects']);
                if (in_array('0', $data['projects'], true)) {
                    array_unshift($selectedProjects, null);
                }
            } else {
                $selectedProjects = [];
            }
            $this->addElements(
                $form,
                $options['teacher'],
                $selectedProjects
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactEducationalTutorReport::class,
            'teacher' => null,
            'translation_domain' => 'wlt_contact'
        ]);
    }
}
