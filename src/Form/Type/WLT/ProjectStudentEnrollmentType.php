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

namespace App\Form\Type\WLT;

use App\Entity\Edu\Group;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\WLT\Project;
use App\Repository\Edu\StudentEnrollmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectStudentEnrollmentType extends AbstractType
{
    private $studentEnrollmentRepository;

    public function __construct(
        StudentEnrollmentRepository $studentEnrollmentRepository
    ) {
        $this->studentEnrollmentRepository = $studentEnrollmentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $groups = $options['groups'];
        $studentEnrollments = $this->studentEnrollmentRepository->findByGroups($groups);

        $builder
            ->add('groups', EntityType::class, [
                'label' => 'form.groups',
                'class' => Group::class,
                'choice_translation_domain' => false,
                'choices' => $groups,
                'choice_label' => function (Group $group) {
                    return $group
                            ->getGrade()
                            ->getTraining()
                            ->getAcademicYear()
                            ->getDescription() . ' - ' . $group->getName();
                },
                'multiple' => true,
                'disabled' => true,
                'required' => false
            ])
            ->add('studentEnrollments', EntityType::class, [
                'label' => 'form.student_enrollments',
                'class' => StudentEnrollment::class,
                'choice_translation_domain' => false,
                'choices' => $studentEnrollments,
                'choice_label' => function (StudentEnrollment $studentEnrollment) {
                    return $studentEnrollment->getPerson()->getLastName() . ', '
                        . $studentEnrollment->getPerson()->getFirstName() . ' ('
                        . $studentEnrollment->getPerson()->getUniqueIdentifier() . ')';
                },
                'group_by' => function (StudentEnrollment $studentEnrollment) {
                    return $studentEnrollment
                            ->getGroup()->getGrade()->getTraining()->getAcademicYear()->getDescription() . ' - '
                        . $studentEnrollment->getGroup()->getName();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'groups' => [],
            'translation_domain' => 'wlt_project'
        ]);
    }
}
