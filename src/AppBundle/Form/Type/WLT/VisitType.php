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

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WLT\Visit;
use AppBundle\Entity\Workcenter;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WorkcenterRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisitType extends AbstractType
{
    /**
     * @var WorkcenterRepository
     */
    private $workcenterRepository;
    /**
     * @var TeacherRepository
     */
    private $teacherRepository;
    /**
     * @var UserExtensionService
     */
    private $userExtensionService;

    public function __construct(
        WorkcenterRepository $workcenterRepository,
        TeacherRepository $teacherRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->workcenterRepository = $workcenterRepository;
        $this->teacherRepository = $teacherRepository;
        $this->userExtensionService = $userExtensionService;
    }

    private function addElements(
        FormInterface $form,
        AcademicYear $academicYear = null,
        $teachers = []
    ) {
        if ($academicYear &&
            $academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            $workcenters = $this->workcenterRepository->findByAcademicYear($academicYear);
            if (!$teachers) {
                $teachers = $this->teacherRepository->findByAcademicYearAndWLT($academicYear);
            }
        } else {
            $workcenters = [];
            $teachers = [];
        }

        $form
            ->add('dateTime', DateTimeType::class, [
                'label' => 'form.datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => true
            ])
            ->add('teacher', EntityType::class, [
                'label' => 'form.teacher',
                'class' => Teacher::class,
                'choices' => $teachers,
                'required' => true
            ])
            ->add('workcenter', EntityType::class, [
                'label' => 'form.workcenter',
                'class' => Workcenter::class,
                'choices' => $workcenters,
                'required' => true
            ])
            ->add('detail', TextareaType::class, [
                'label' => 'form.detail',
                'required' => false,
                'attr' => ['rows' => 10]
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

            if ($data->getWorkcenter()) {
                $academicYear = $data->getWorkcenter()->getAcademicYear();
            } else {
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            $this->addElements($form, $academicYear, $options['teachers']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            /** @var Workcenter $workcenter */
            $workcenter = isset($data['workcenter']) ?
                $this->workcenterRepository->find($data['workcenter']) : null;

            $academicYear = (null === $workcenter) ?
                $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear() :
                $workcenter->getAcademicYear();

            $this->addElements($form, $academicYear, $options['teachers']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Visit::class,
            'teachers' => [],
            'translation_domain' => 'wlt_visit'
        ]);
    }
}
