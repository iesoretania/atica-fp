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
use App\Repository\WLT\WLTTeacherRepository;
use App\Repository\WorkcenterRepository;
use App\Service\UserExtensionService;
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
    private $workcenterRepository;
    private $wltTeacherRepository;
    private $userExtensionService;
    private $projectRepository;
    private $contactMethodRepository;
    private $contactRepository;
    private $translator;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        WLTTeacherRepository $wltTeacherRepository,
        ProjectRepository $projectRepository,
        ContactRepository $contactRepository,
        ContactMethodRepository $contactMethodRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->projectRepository = $projectRepository;
        $this->userExtensionService = $userExtensionService;
        $this->contactRepository = $contactRepository;
        $this->contactMethodRepository = $contactMethodRepository;
        $this->translator = $translator;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    private function addElements(
        FormInterface $form,
        Teacher $teacher,
        Workcenter $workcenter = null
    ) {
        $workcenters = $this->contactRepository->findWorkcentersByTeacher($teacher);

        $academicYear = $teacher->getAcademicYear();
        $methods = $this->contactMethodRepository->findEnabledByAcademicYear($academicYear);
        $methodsChoices = [
            null,
        ];
        foreach ($methods as $method) {
            $methodsChoices[] = $method;
        }
        $projects = [null];
        if ($workcenter !== null) {
            $potentialProjects = $this->projectRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
            foreach ($potentialProjects as $project) {
                $projects[] = $project;
            }
        }

        $form
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choices' => $workcenters,
                'placeholder' => 'form.workcenter.all',
                'required' => false
            ])
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
                $data->getWorkcenter()
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['workcenter']) {
                /** @var Workcenter $workcenter */
                $workcenter = $this->workcenterRepository->find($data['workcenter']);
            } else {
                $workcenter = null;
            }

            $this->addElements(
                $form,
                $options['teacher'],
                $workcenter
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
