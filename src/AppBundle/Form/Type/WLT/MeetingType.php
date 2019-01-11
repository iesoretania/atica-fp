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
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WLT\Meeting;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\StudentEnrollmentRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class MeetingType extends AbstractType
{
    /**
     * @var AcademicYearRepository
     */
    private $academicYearRepository;
    /**
     * @var StudentEnrollmentRepository
     */
    private $studentEnrollmentRepository;
    /**
     * @var TeacherRepository
     */
    private $teacherRepository;
    /**
     * @var UserExtensionService
     */
    private $userExtensionService;

    public function __construct(
        AcademicYearRepository $academicYearRepository,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        TeacherRepository $teacherRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->academicYearRepository = $academicYearRepository;
        $this->studentEnrollmentRepository = $studentEnrollmentRepository;
        $this->teacherRepository = $teacherRepository;
        $this->userExtensionService = $userExtensionService;
    }

    private function addElements(
        FormInterface $form,
        AcademicYear $academicYear = null,
        $groups = []
    ) {
        if (
            $academicYear &&
            $academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            if ($groups) {
                $studentEnrollments = $this->studentEnrollmentRepository
                    ->findByAcademicYearAndGroupsAndWLT($academicYear, $groups);
            } else {
                $studentEnrollments = $this->studentEnrollmentRepository->findByAcademicYearAndWLT($academicYear);
            }

            $teachers = $this->teacherRepository->findByAcademicYearAndWLT($academicYear);
        } else {
            $studentEnrollments = [];
            $teachers = [];
        }

        $form
            ->add('academicYear', null, [
                'label' => 'form.academic_year',
                'choices' => [$academicYear],
                'required' => true
            ])
            ->add('date', DateType::class, [
                'label' => 'form.date',
                'widget' => 'single_text',
                'required' => true
            ])
            ->add('studentEnrollments', EntityType::class, [
                'label' => 'form.students',
                'class' => StudentEnrollment::class,
                'choices' => $studentEnrollments,
                'multiple' => true,
                'required' => false
            ])
            ->add('teachers', EntityType::class, [
                'label' => 'form.teachers',
                'class' => Teacher::class,
                'choices' => $teachers,
                'constraints' => [
                    new Count(['min' => 1])
                ],
                'multiple' => true,
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

            $academicYear = $data->getAcademicYear();
            $this->addElements($form, $academicYear, $options['groups']);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();

            /** @var AcademicYear $academicYear */
            $academicYear = isset($data['academicYear']) ?
                $this->academicYearRepository->find($data['academicYear']) :
                null;

            $this->addElements($form, $academicYear, $options['groups']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Meeting::class,
            'groups' => [],
            'translation_domain' => 'wlt_meeting'
        ]);
    }
}
