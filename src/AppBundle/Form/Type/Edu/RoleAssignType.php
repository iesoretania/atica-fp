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

use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Person;
use AppBundle\Entity\Role;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleAssignType extends AbstractType
{
    /** @var RoleRepository */
    private $roleRepository;

    /** @var TeacherRepository */
    private $teacherRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        RoleRepository $roleRepository,
        TeacherRepository $teacherRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->roleRepository = $roleRepository;
        $this->teacherRepository = $teacherRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);
        $persons = array_map(function (Teacher $teacher) {
           return $teacher->getPerson();
        }, $teachers);

        foreach (Role::ROLES as $roleName) {
            $assignedRole = $this->roleRepository->findByOrganizationAndRole($organization, $roleName);
            $currentPersons = array_map(function (Role $role) {
                return $role->getPerson();
            }, $assignedRole);

            $builder
                ->add($roleName, EntityType::class, [
                    'label' => 'form.role.' . $roleName,
                    'choice_translation_domain' => false,
                    'choices' => $persons,
                    'class' => Person::class,
                    'data' => $currentPersons,
                    'multiple' => true,
                    'required' => false
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'role'
        ]);
    }
}
