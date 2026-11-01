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

namespace App\Form\Type\WltModule;

use App\Entity\Edu\Group;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\WltModule\Project;
use App\Form\Type\Edu\StudentEnrollmentChoiceType;
use App\Repository\Edu\StudentEnrollmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectStudentEnrollmentType extends AbstractType
{
    public function __construct(private readonly StudentEnrollmentRepository $studentEnrollmentRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $groups = $options['groups'];
        $studentEnrollments = $this->studentEnrollmentRepository->findByGroups($groups);

        $builder
            ->add('groups', EntityType::class, [
                'label' => 'form.groups',
                'help' => 'form.groups.fixed_help',
                'class' => Group::class,
                'choice_translation_domain' => false,
                'choices' => $groups,
                'choice_label' => fn(Group $group): string => $group
                        ->getGrade()
                        ->getTraining()
                        ->getAcademicYear()
                        ->getDescription() . ' - ' . $group->getName(),
                'multiple' => true,
                'disabled' => true,
                'required' => false,
                'attr' => ['data-controller' => 'choice-select']
            ])
            ->add('studentEnrollments', StudentEnrollmentChoiceType::class, [
                'label' => 'form.student_enrollments',
                'help' => 'form.student_enrollments.help',
                'choices' => $studentEnrollments,
                'group_by' => fn(StudentEnrollment $studentEnrollment): string => $studentEnrollment
                        ->getGroup()->getGrade()->getTraining()->getAcademicYear()->getDescription() . ' - '
                    . $studentEnrollment->getGroup()->getName(),
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'groups' => [],
            'translation_domain' => 'wlt_project'
        ]);
    }
}
