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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Project;
use App\Entity\Workcenter;
use App\Form\Model\WLT\ContactEducationalTutorReport;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WLT\WLTStudentEnrollmentRepository;
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
    private $wltStudentEnrollmentRepository;
    private $contactMethodRepository;
    private $translator;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        WLTTeacherRepository $wltTeacherRepository,
        ProjectRepository $projectRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        ContactMethodRepository $contactMethodRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->wltTeacherRepository = $wltTeacherRepository;
        $this->projectRepository = $projectRepository;
        $this->userExtensionService = $userExtensionService;
        $this->wltStudentEnrollmentRepository = $wltStudentEnrollmentRepository;
        $this->contactMethodRepository = $contactMethodRepository;
        $this->translator = $translator;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    private function addElements(
        FormInterface $form,
        AcademicYear $academicYear = null,
        Workcenter $workcenter = null,
        $selectedProjects = [],
        $teachers = []
    ) {
        $workcenters = $this->workcenterRepository->findAll();
        $methodsChoices = [];

        if ($academicYear &&
            $academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            if (!$teachers) {
                $teachers = $this->wltTeacherRepository->findByAcademicYear($academicYear);
            }
            $methods = $this->contactMethodRepository->findEnabledByAcademicYear($academicYear);
            $methodsChoices = [
                null,
            ];
            foreach ($methods as $method) {
                $methodsChoices[] = $method;
            }
        } else {
            $teachers = [];
        }
        if ($workcenter !== null) {
            $projects = $this->projectRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
        } else {
            $projects = [];
        }
        $canSelectProjects = (is_array($projects) || $projects instanceof \Countable ? count($projects) : 0) > 0;

        $form
            ->add('teacher', EntityType::class, [
                'label' => 'form.teacher',
                'class' => Teacher::class,
                'choices' => $teachers,
                'placeholder' => 'form.teacher.none',
                'required' => true
            ])
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choices' => $workcenters,
                'placeholder' => 'form.workcenter.all',
                'required' => false
            ])
            ->add('projects', EntityType::class, [
                'label' => 'form.projects',
                'class' => Project::class,
                'choices' => $projects,
                'disabled' => !$canSelectProjects,
                'expanded' => $canSelectProjects,
                'mapped' => $canSelectProjects,
                'multiple' => $canSelectProjects,
                'placeholder' => 'form.projects.none',
                'required' => false
            ])
            ->add('contactMethods', ChoiceType::class, [
                'label' => 'form.methods',
                //'class' => ContactMethod::class,
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

            if ($data->getTeacher()) {
                $academicYear = $data->getTeacher()->getAcademicYear();
            } else {
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            $this->addElements(
                $form,
                $academicYear,
                $data->getWorkcenter(),
                $data->getProjects(),
                $options['teachers']
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['teacher']) {
                $academicYear = $this->wltTeacherRepository->find($data['teacher'])->getAcademicYear();
            } else {
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            if ($data['workcenter']) {
                /** @var Workcenter $workcenter */
                $workcenter = $this->workcenterRepository->find($data['workcenter']);
                $selectedProjects = isset($data['projects'])
                    ? $this->projectRepository->findByIds($data['projects'])
                    : [];
            } else {
                $workcenter = null;
                $selectedProjects = [];
            }

            $this->addElements(
                $form,
                $academicYear,
                $workcenter,
                $selectedProjects,
                $options['teachers']
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
            'teachers' => [],
            'translation_domain' => 'wlt_contact'
        ]);
    }
}
