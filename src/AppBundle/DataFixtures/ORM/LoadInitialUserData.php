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

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Person;
use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LoadInitialUserData extends Fixture
{
    /** @var UserPasswordEncoderInterface $passwordEncoder */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $person = new Person();

        $person
            ->setFirstName('Admin')
            ->setLastName('Admin')
            ->setGender(User::GENDER_NEUTRAL);

        $manager->persist($person);

        $userAdmin = new User();
        $userAdmin
            ->setLoginUsername('admin')
            ->setEnabled(true)
            ->setGlobalAdministrator(true)
            ->setPassword($this->passwordEncoder->encodePassword($userAdmin, 'admin'))
            ->setForcePasswordChange(true);

        $person
            ->setUser($userAdmin);

        $manager->persist($userAdmin);

        $manager->flush();
    }
}
