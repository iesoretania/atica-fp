<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\WptModule\Shift;
use App\Security\OrganizationVoter;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Convocatorias de FCT con enlace a su informe de encuestas (alumnado, empresas o centro), por curso académico. */
#[AsLiveComponent('Wpt:ReportList', template: 'components/Wpt/ReportList.html.twig')]
final class ReportList extends AbstractListComponent
{
    /** Ruta del informe al que enlaza cada convocatoria (se firma con el resto de propiedades). */
    #[LiveProp]
    public string $routeName = '';

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_MANAGER, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('s')
            ->distinct()
            ->from(Shift::class, 's')
            ->join('s.subject', 'su')
            ->join('su.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('s.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('gr.name LIKE :tq')
                ->orWhere('s.name LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $this->organization())) {
            $queryBuilder
                ->andWhere('(d.head IS NOT NULL AND h.person = :manager)')
                ->setParameter('manager', $this->security->getUser());
        }

        return $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
