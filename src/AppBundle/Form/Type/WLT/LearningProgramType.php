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

use AppBundle\Entity\Company;
use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\User;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\LearningProgram;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class LearningProgramType extends AbstractType
{
    /** @var UserExtensionService */
    private $userExtensionService;

    /** @var AcademicYearRepository */
    private $academicYearRepository;

    /** @var Security */
    private $security;

    /** @var ActivityRealizationRepository */
    private $activityRealizationRepository;

    /** @var TrainingRepository */
    private $trainingRepository;

    public function __construct(
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        TrainingRepository $trainingRepository,
        Security $security
    ) {
        $this->userExtensionService = $userExtensionService;
        $this->academicYearRepository = $academicYearRepository;
        $this->activityRealizationRepository = $activityRealizationRepository;
        $this->trainingRepository = $trainingRepository;
        $this->security = $security;
    }

    public function addElements(
        FormInterface $form,
        AcademicYear $academicYear = null,
        Training $training = null
    ) {
        $organization = $this->userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (false === $this->security->isGranted(OrganizationVoter::MANAGE, $organization) &&
            false === $this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization)
        ) {
            $trainings = $this->trainingRepository->findByAcademicYearAndDepartmentHead(
                $academicYear,
                $user->getPerson()
            );
        } else {
            $trainings = $this->trainingRepository->findByAcademicYearAndWLT($academicYear);
        }

        $academicYears = $this->security->isGranted(OrganizationVoter::MANAGE, $organization) ?
            $this->academicYearRepository->findAllByOrganization($organization) :
            [$academicYear];

        if ($training) {
            $activityRealizations = $this->activityRealizationRepository->findByTraining($training);
        } else {
            $activityRealizations = [];
        }
        $form
            ->add('academicYear', EntityType::class, [
                'label' => 'form.academic_year',
                'mapped' => false,
                'class' => AcademicYear::class,
                'choice_translation_domain' => false,
                'choices' => $academicYears,
                'data' => $academicYear,
                'required' => true
            ])
            ->add('training', EntityType::class, [
                'label' => 'form.training',
                'class' => Training::class,
                'choice_translation_domain' => false,
                'choices' => $trainings,
                'data' => $training,
                'placeholder' => 'form.training.none',
                'required' => true
            ])
            ->add('company', EntityType::class, [
                'label' => 'form.company',
                'class' => Company::class,
                'choice_label' => 'fullName',
                'choice_translation_domain' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name');
                },
                'placeholder' => 'form.company.none',
                'required' => true
            ])
            ->add('activityRealizations', EntityType::class, [
                'label' => 'form.activity_realizations',
                'class' => ActivityRealization::class,
                'expanded' => false,
                'group_by' => function (ActivityRealization $ar) {
                    return (string) $ar->getActivity();
                },
                'multiple' => true,
                'required' => false,
                'choices' => $activityRealizations
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $academicYear = $data->getTraining() ?
                $data->getTraining()->getAcademicYear() :
                null;

            $training = $data->getTraining();

            $this->addElements($form, $academicYear, $training);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            /** @var AcademicYear $academicYear */
            $academicYear = isset($data['academicYear']) ?
                $this->academicYearRepository->find($data['academicYear']) :
                null;

            /** @var Training $training */
            $training = isset($data['training']) ?
                $this->trainingRepository->find($data['training']) :
                null;

            $this->addElements($form, $academicYear, $training);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LearningProgram::class,
            'translation_domain' => 'wlt_learning_program'
        ]);
    }
}
