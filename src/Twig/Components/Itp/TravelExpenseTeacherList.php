<?php

declare(strict_types=1);

namespace App\Twig\Components\Itp;

use App\Entity\Person;
use App\Repository\ItpModule\TravelExpenseRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/** Resumen de gastos de desplazamiento por docente en FFEOE de un curso académico. */
#[AsLiveComponent('Itp:TravelExpenseTeacherList', template: 'components/Itp/TravelExpenseTeacherList.html.twig')]
final class TravelExpenseTeacherList extends AbstractListComponent
{
    private TravelExpenseRepository $travelExpenseRepository;

    #[Required]
    public function setTravelExpenseRepository(TravelExpenseRepository $travelExpenseRepository): void
    {
        $this->travelExpenseRepository = $travelExpenseRepository;
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_EXPENSE, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return ['teacher' => ['p.lastName', 'p.firstName']];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $organization = $this->organization();
        $person = $this->security->getUser();
        if (!$person instanceof Person) {
            throw new AccessDeniedException();
        }
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization)
            || $this->security->isGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        return $this->travelExpenseRepository->createTeacherTravelExpenseStatsByPersonAndAcademicYearQueryBuilder(
            $this->academicYear,
            $isManager,
            $person,
            $this->q !== '' ? $this->q : null
        );
    }
}
