<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Form\Type\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Workcenter;
use App\Entity\WPT\Agreement;
use App\Entity\WPT\Contact;
use App\Repository\WorkcenterRepository;
use App\Repository\WPT\AgreementRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Repository\WPT\WPTWorkcenterRepository;
use App\Service\UserExtensionService;
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
    public function __construct(private readonly WorkcenterRepository $workcenterRepository, private readonly UserExtensionService $userExtensionService, private readonly WPTTeacherRepository $WPTTeacherRepository, private readonly AgreementRepository $agreementRepository, private readonly WPTWorkcenterRepository $WPTWorkcenterRepository)
    {
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    private function addElements(
        FormInterface $form,
        ?AcademicYear $academicYear,
        ?Workcenter $workcenter,
        ?Teacher $teacher,
        $selectedAgreements,
        $teachers,
        \DateTimeInterface $dateTime
    ): void {
        $workcenters = [];

        if ($academicYear &&
            $academicYear->getOrganization() === $this->userExtensionService->getCurrentOrganization()
        ) {
            if (!$teachers) {
                $teachers = $this->WPTTeacherRepository->findByAcademicYear($academicYear);
            }
        } else {
            $teachers = [];
        }
        $studentEnrollments = [];

        if ($teacher instanceof Teacher) {
            $workcenters = $this->WPTWorkcenterRepository->findByWPTEducationalTutor($teacher);
        }
        if ($teacher && $workcenter && $dateTime) {
            $agreements = $this->agreementRepository
                ->findByWorkcenterAndTeacher($workcenter, $teacher);

            $startDate = clone $dateTime;
            $startDate->setTime(0, 0);
            $endDate = clone $dateTime;
            $endDate->add(new \DateInterval('P1D'));

            /** @var Agreement $agreement */
            foreach ($selectedAgreements as $agreement) {
                foreach ($agreement->getAgreementEnrollments() as $agreementEnrollment) {
                    if (($agreementEnrollment->getEducationalTutor() === $teacher
                        || $agreementEnrollment->getAdditionalEducationalTutor() === $teacher)
                        && (!$agreement->getStartDate() || $endDate >= $agreement->getStartDate())
                        && (!$agreement->getEndDate() || $startDate <= $agreement->getEndDate())) {
                        $studentEnrollments[] = $agreementEnrollment->getStudentEnrollment();
                    }
                }
            }
        } else {
            $agreements = [];
        }

        $canSelectAgreements =
            (
                is_countable($agreements)
                ? count($agreements)
                : 0
            ) > 0;

        $canSelectStudentEnrollments = $studentEnrollments !== [];

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
            ->add('agreements', EntityType::class, [
                'label' => 'form.agreements',
                'class' => Agreement::class,
                'choices' => $agreements,
                'disabled' => !$canSelectAgreements,
                'expanded' => $canSelectAgreements,
                'mapped' => $canSelectAgreements,
                'multiple' => $canSelectAgreements,
                'placeholder' => 'form.agreements.none',
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
            ->add('detail', TextareaType::class, [
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

            /** @var Contact $data */
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
                $data->getTeacher(),
                $data->getAgreements(),
                $options['teachers'],
                $data->getDateTime()
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data['teacher']) {
                /** @var Teacher $teacher */
                $teacher = $this->WPTTeacherRepository->find($data['teacher']);
                $academicYear = $teacher->getAcademicYear();
            } else {
                $teacher = null;
                $academicYear = $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
            }

            if ($data['workcenter']) {
                /** @var Workcenter $workcenter */
                $workcenter = $this->workcenterRepository->find($data['workcenter']);
                $selectedAgreements = isset($data['agreements'])
                    ? $this->agreementRepository->findByIds($data['agreements'])
                    : [];
            } else {
                $workcenter = null;
                $selectedAgreements = [];
            }

            $this->addElements(
                $form,
                $academicYear,
                $workcenter,
                $teacher,
                $selectedAgreements,
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
            'translation_domain' => 'wpt_visit'
        ]);
    }
}
