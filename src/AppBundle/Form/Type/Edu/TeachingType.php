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

namespace AppBundle\Form\Type\Edu;

use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Edu\Teaching;
use AppBundle\Repository\Edu\TeacherRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeachingType extends AbstractType
{
    /** @var TeacherRepository */
    private $teacherRepository;

    public function __construct(
        TeacherRepository $teacherRepository
    ) {
        $this->teacherRepository = $teacherRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Subject $subject */
        $subject = $options['subject'];
        $groups = $subject->getGrade()->getGroups();
        if (count($groups) > 0) {
            $group = $groups[0];
        }
        $academicYear = $subject->getGrade()->getTraining()->getAcademicYear();

        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);

        $builder
            ->add('subject', EntityType::class, [
                'label' => 'form.subject',
                'class' => Subject::class,
                'choice_translation_domain' => false,
                'choices' => [$subject],
                'disabled' => true,
                'required' => true
            ])
            ->add('group', EntityType::class, [
                'label' => 'form.group',
                'class' => Group::class,
                'choice_translation_domain' => false,
                'choices' => $groups,
                'data' => $group,
                'placeholder' => 'form.none',
                'required' => true
            ])
            ->add('teacher', EntityType::class, [
                'label' => 'form.teacher',
                'class' => Teacher::class,
                'choice_translation_domain' => false,
                'choices' => $teachers,
                'placeholder' => 'form.none',
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Teaching::class,
            'subject' => null,
            'translation_domain' => 'edu_teaching'
        ]);
    }
}
