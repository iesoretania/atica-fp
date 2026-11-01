<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\TravelExpense;
use App\Repository\ItpModule\TravelExpenseRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TravelExpenseVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Gastos de desplazamiento de un docente en FFEOE, con filtros, orden y paginación dinámicos. */
#[AsLiveComponent('Itp:TravelExpenseList', template: 'components/Itp/TravelExpenseList.html.twig')]
final class TravelExpenseList extends AbstractListComponent
{
    #[LiveProp]
    public Teacher $teacher;

    private TravelExpenseRepository $travelExpenseRepository;

    #[Required]
    public function setTravelExpenseRepository(TravelExpenseRepository $travelExpenseRepository): void
    {
        $this->travelExpenseRepository = $travelExpenseRepository;
    }

    public function allowNew(): bool
    {
        return $this->security->isGranted(ItpOrganizationVoter::ITP_CREATE_EXPENSE, $this->organization())
            && $this->security->isGranted(TravelExpenseVoter::MANAGE, (new TravelExpense())->setTeacher($this->teacher));
    }

    protected function authorize(): void
    {
        $organization = $this->organization();
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_EXPENSE, $organization);
        if ($this->teacher->getAcademicYear()->getOrganization() !== $organization) {
            throw new AccessDeniedException();
        }
    }

    public function filterDefinitions(): array
    {
        return [
            'verified' => [
                'type' => 'select',
                'label' => $this->translator->trans('header.verified', [], 'itp_travel_expense'),
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
        $queryBuilder = $this->travelExpenseRepository->createStatsByTeacherOrderByDateTimeFilterQueryBuilder($this->teacher, $this->q !== '' ? $this->q : null);

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
