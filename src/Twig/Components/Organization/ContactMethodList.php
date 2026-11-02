<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Entity\Edu\ContactMethod;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Tipos de contacto de un curso académico. */
#[AsLiveComponent('Organization:ContactMethodList', template: 'components/Organization/ContactMethodList.html.twig')]
final class ContactMethodList extends AbstractAcademicYearList
{
    protected function sortableColumns(): array
    {
        return ['description' => 'cm.description', 'enabled' => 'cm.enabled'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('cm')
            ->from(ContactMethod::class, 'cm')
            ->orderBy('cm.description');

        if ($this->q !== '') {
            $queryBuilder->where('cm.description LIKE :tq')->setParameter('tq', '%' . $this->q . '%');
        }

        return $queryBuilder
            ->andWhere('cm.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
