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

namespace AppBundle\Form\Type\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\User;
use AppBundle\Repository\Edu\GroupRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentEnrollmentType extends AbstractType
{
    private $groupRepository;

    public function __construct(
        GroupRepository $groupRepository
    ) {
        $this->groupRepository = $groupRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AcademicYear $academicYear */
        $academicYear = $options['academic_year'];

        $groups = $this->groupRepository->findByAcademicYear($academicYear);

        $builder
            ->add('group', EntityType::class, [
                'label' => 'form.group',
                'class' => Group::class,
                'choices' => $groups,
                'required' => true
            ])
            ->add('personFirstName', null, [
                'label' => 'form.first_name',
                'property_path' => 'person.firstName',
                'required' => true
            ])
            ->add('personLastName', null, [
                'label' => 'form.last_name',
                'property_path' => 'person.lastName',
                'required' => true
            ])
            ->add('personGender', ChoiceType::class, [
                'label' => 'form.gender',
                'expanded' => true,
                'property_path' => 'person.gender',
                'choices' => [
                    'form.gender.neutral' => User::GENDER_NEUTRAL,
                    'form.gender.male' => User::GENDER_MALE,
                    'form.gender.female' => User::GENDER_FEMALE
                ],
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StudentEnrollment::class,
            'translation_domain' => 'edu_student_enrollment',
            'academic_year' => null
        ]);
    }
}
