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

use AppBundle\Repository\OrganizationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadInitialOrganizationData extends Fixture
{
    /**
     * @var OrganizationRepository
     */
    private $organizationRepository;

    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    public function load(ObjectManager $manager)
    {
        $organization = $this->organizationRepository->createEducationalOrganization();

        $organization
            ->setName('I.E.S. Test')
            ->setCode('23999999')
            ->setCity('Linares');

        $manager->flush();

        $this->setReference('organization', $organization);
    }

    public function getDependencies()
    {
        return [
            LoadInitialUserData::class
        ];
    }
}
