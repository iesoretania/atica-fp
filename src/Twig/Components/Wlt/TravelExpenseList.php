<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\Edu\Teacher;
use App\Entity\WltModule\TravelExpense;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Security\WltModule\TravelExpenseVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Gastos de desplazamiento de un docente en FP dual, con filtros, orden y paginación dinámicos. */
#[AsLiveComponent('Wlt:TravelExpenseList', template: 'components/Wlt/TravelExpenseList.html.twig')]
final class TravelExpenseList extends AbstractListComponent
{
    #[LiveProp]
    public Teacher $teacher;

    public function allowNew(): bool
    {
        return $this->security->isGranted(WltOrganizationVoter::WLT_CREATE_EXPENSE, $this->organization())
            && $this->security->isGranted(TravelExpenseVoter::MANAGE, (new TravelExpense())->setTeacher($this->teacher));
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_ACCESS_EXPENSE, $organization);
        if ($this->teacher->getAcademicYear()->getOrganization() !== $organization) {
            throw new AccessDeniedException();
        }
    }

    public function filterDefinitions(): array
    {
        return [
            'verified' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.verified', [], 'wlt_travel_expense'),
                'choices' => ['1' => $this->translator->trans('filters.yes', [], 'general'), '0' => $this->translator->trans('filters.no', [], 'general')],
            ],
            'from' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_from', [], 'general')],
            'to' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_to', [], 'general')],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['route' => 'tr.description', 'distance' => 'tr.distance', 'date' => 'te.fromDateTime'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('te', 'tr')
            ->addSelect('COUNT(a)')
            ->distinct()
            ->from(TravelExpense::class, 'te')
            ->join('te.travelRoute', 'tr')
            ->leftJoin('te.agreements', 'a')
            ->groupBy('te')
            ->addOrderBy('te.fromDateTime', 'DESC');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('a.internalCode LIKE :tq')
                ->orWhere('tr.description LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        $queryBuilder
            ->andWhere('te.teacher = :teacher')
            ->setParameter('teacher', $this->teacher);

        if (($verified = $this->filter('verified')) !== '') {
            $queryBuilder->andWhere('tr.verified = :filter_verified')->setParameter('filter_verified', $verified === '1');
        }
        if (($from = $this->filter('from')) !== '') {
            $queryBuilder->andWhere('te.fromDateTime >= :filter_from')->setParameter('filter_from', new \DateTime($from . ' 00:00:00'));
        }
        if (($to = $this->filter('to')) !== '') {
            $queryBuilder->andWhere('te.fromDateTime < :filter_to')->setParameter('filter_to', (new \DateTime($to . ' 00:00:00'))->modify('+1 day'));
        }

        return $queryBuilder;
    }
}
