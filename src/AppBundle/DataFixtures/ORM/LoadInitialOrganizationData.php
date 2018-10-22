<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

use AppBundle\Entity\ICT\Priority;
use AppBundle\Entity\Organization;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadInitialOrganizationData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function load(ObjectManager $manager)
    {
        $organization = new Organization();
        $organization
            ->setName('I.E.S. Test')
            ->setCode('23999999')
            ->setCity('Linares');

        $manager->persist($organization);

        $this->setReference('organization', $organization);

        $priorities = [
            [
                'level_number' => 100,
                'name' => 'Crítica',
                'color' => '#000000',
                'days' => 5,
                'description' =>
                    'Incidencias críticas que impiden completamente el desarrollo de las actividades docentes'
            ],
            [
                'level_number' => 80,
                'name' => 'Urgente',
                'color' => '#ff0000',
                'days' => 10,
                'description' => 'Incidencias importantes que deben tener prioridad alta y deben resolverse pronto'
            ],
            [
                'level_number' => 60,
                'name' => 'Alta',
                'color' => '#ffa000',
                'days' => 15,
                'description' => 'Incidencias que dificultan el desarrollo normal de las actividades docentes'
            ],
            [
                'level_number' => 40,
                'name' => 'Media',
                'color' => '#c0c000',
                'days' => 20,
                'description' => 'Incidencias que afectan poco a las actividades docentes'
            ],
            [
                'level_number' => 20,
                'name' => 'Baja',
                'color' => '#00a000',
                'days' => 30,
                'description' => 'Incidencias que afectan poco a las actividades docentes'
            ],
            [
                'level_number' => 0,
                'name' => 'Ninguna',
                'color' => '#ffffff',
                'days' => null,
                'description' => 'Incidencias no relevantes que no tienen prioridad alguna'
            ]
        ];

        foreach ($priorities as $priorityData) {
            $priority = new Priority();
            $priority
                ->setOrganization($organization)
                ->setLevelNumber($priorityData['level_number'])
                ->setName($priorityData['name'])
                ->setColor($priorityData['color'])
                ->setDescription($priorityData['description']);

            if ($priorityData['days'] !== null) {
                $priority->setDays($priorityData['days']);
            }

            $manager->persist($priority);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 20;
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
