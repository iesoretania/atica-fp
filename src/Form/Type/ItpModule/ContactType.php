<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\Contact;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Workcenter;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\ItpModule\StudentEnrollmentRepository;
use App\Repository\ItpModule\TeacherRepository;
use App\Repository\ItpModule\TrainingProgramRepository;
use App\Repository\WorkcenterRepository;
use App\Service\UserExtensionService;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function __construct(
        private readonly WorkcenterRepository        $workcenterRepository,
        private readonly TeacherRepository           $teacherRepository,
        private readonly TrainingProgramRepository   $trainingProgramRepository,
        private readonly StudentEnrollmentRepository $itpStudentEnrollmentRepository,
        private readonly ContactMethodRepository     $contactMethodRepository,
        private readonly UserExtensionService        $userExtensionService)
    {
    }

    private function addElements(
        FormInterface      $form,
        AcademicYear       $academicYear,
        ?Workcenter        $workcenter,
                           $selectedTrainingPrograms,
        array              $teachers,
        \DateTimeInterface $dateTime
    ): void {
        $workcenters = $this->workcenterRepository->findAllSorted();
        $methods = [];

        if ($academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            if (!$teachers) {
                $teachers = $this->teacherRepository->findByAcademicYear($academicYear);
            }
            $methods = $this->contactMethodRepository->findEnabledByAcademicYear($academicYear);
        } else {
            $teachers = [];
        }
        $studentEnrollments = [];
        if ($workcenter instanceof Workcenter) {
            $trainingPrograms = $this->trainingProgramRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
            if (count($trainingPrograms) > 0) {
                $studentEnrollments =
                    $this->itpStudentEnrollmentRepository
                        ->findByWorkcenterTrainingProgramsAndAgreementDate($workcenter, $selectedTrainingPrograms, $dateTime);
            }
        } else {
            $trainingPrograms = [];
        }
        $canSelectTrainingPrograms = count($trainingPrograms) > 0;
        $canSelectStudentEnrollments = count($studentEnrollments) > 0;

        $form
            ->add('dateTime', DateTimeType::class, [
                'label' => 'form.datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'model_timezone' => 'UTC',
                'required' => true
            ])
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
                'placeholder' => 'form.workcenter.none',
                'required' => true
            ])
            ->add('method', EntityType::class, [
                'label' => 'form.method',
                'class' => ContactMethod::class,
                'choices' => $methods,
                'placeholder' => 'form.method.on-site',
                'required' => false
            ])
            ->add('trainingPrograms', EntityType::class, [
                'label' => 'form.training_programs',
                'class' => TrainingProgram::class,
                'choices' => $trainingPrograms,
                'disabled' => !$canSelectTrainingPrograms,
                'expanded' => $canSelectTrainingPrograms,
                'mapped' => $canSelectTrainingPrograms,
                'multiple' => $canSelectTrainingPrograms,
                'placeholder' => 'form.training_programs.none',
                'required' => false
            ])
            ->add('studentEnrollments', EntityType::class, [
                'label' => 'form.student_enrollments',
                'class' => StudentEnrollment::class,
                'choices' => $studentEnrollments,
                'disabled' => !$canSelectStudentEnrollments,
                'expanded' => $canSelectStudentEnrollments,
                'mapped' => $canSelectStudentEnrollments,
                'multiple' => $canSelectStudentEnrollments,
                'placeholder' => 'form.student_enrollments.none',
                'required' => false
            ])
            ->add('detail', CKEditorType::class, [
                'label' => 'form.detail',
                'required' => false,
                'attr' => ['rows' => 10]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
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
                $data->getTrainingPrograms()->toArray(),
                $options['teachers'],
                $data->getDateTime()
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['teacher']) {
                $teacher = $this->teacherRepository->find($data['teacher']);
                assert($teacher instanceof Teacher);
                $academicYear = $teacher->getAcademicYear();
            } else {
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            assert($academicYear instanceof AcademicYear);

            if ($data['workcenter']) {
                $workcenter = $this->workcenterRepository->find($data['workcenter']);
                assert($workcenter instanceof Workcenter);
                $selectedProjects = isset($data['trainingPrograms'])
                    ? $this->trainingProgramRepository->findAllInListByIdAndAcademicYear($data['trainingPrograms'], $academicYear)
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
                $options['teachers'],
                date_create($data['dateTime']['date'] . ' ' . $data['dateTime']['time'])
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'teachers' => [],
            'translation_domain' => 'itp_contact'
        ]);
    }
}
