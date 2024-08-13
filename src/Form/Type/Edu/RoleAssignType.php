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

namespace App\Form\Type\Edu;

use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Role;
use App\Repository\Edu\TeacherRepository;
use App\Repository\RoleRepository;
use App\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleAssignType extends AbstractType
{
    public function __construct(private readonly RoleRepository $roleRepository, private readonly TeacherRepository $teacherRepository, private readonly UserExtensionService $userExtensionService)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $teachers = $this->teacherRepository->findByAcademicYear($academicYear);
        $persons = array_map(fn(Teacher $teacher) => $teacher->getPerson(), $teachers);

        foreach (Role::ROLES as $roleName) {
            $assignedRole = $this->roleRepository->findByOrganizationAndRole($organization, $roleName);
            $currentPersons = array_map(fn(Role $role) => $role->getPerson(), $assignedRole);

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
