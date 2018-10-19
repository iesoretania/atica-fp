<?php

namespace AppBundle\Repository\ICT;

use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;

class TicketRepository extends EntityRepository
{
    /**
     * Pasado un array de ids de localizaciones, devolver la lista de objetos que pertenezcan a la organización actual
     * @param $items
     * @param Organization $organization
     * @return array
     */
    public function findInListByIdAndOrganization($items, Organization $organization)
    {
        return $this->createQueryBuilder('t')
            ->where('t.id IN (:items)')
            ->andWhere('t.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueOn', 'DESC')
            ->addOrderBy('t.createdOn', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
