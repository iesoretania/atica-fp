<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;

class LocationRepository extends EntityRepository
{
    /**
     * Pasado un array de ids de localizaciones, devolver la lista de objetos que pertenezcan a la organización actual
     * @param $items
     * @param Organization $organization
     * @return array
     */
    public function findAllInListByIdAndOrganization($items, Organization $organization) {
        return $this->createQueryBuilder('l')
            ->where('l.id IN (:items)')
            ->andWhere('l.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('l.name')
            ->getQuery()
            ->getResult();
    }
}
