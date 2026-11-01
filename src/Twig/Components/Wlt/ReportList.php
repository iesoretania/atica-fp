<?php

declare(strict_types=1);

namespace App\Twig\Components\Wlt;

use App\Entity\WltModule\Project;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Proyectos de FP dual con enlace a un informe (reuniones, asistencia, evaluación, programa formativo o encuestas), por curso académico. */
#[AsLiveComponent('Wlt:ReportList', template: 'components/Wlt/ReportList.html.twig')]
final class ReportList extends AbstractListComponent
{
    /** Ruta del informe al que enlaza cada proyecto (se firma con el resto de propiedades). */
    #[LiveProp]
    public string $routeName = '';

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WltOrganizationVoter::WLT_MANAGE, $this->organization());
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
    }

    protected function sortableColumns(): array
    {
        return ['name' => 'p.name', 'manager' => ['m.lastName', 'm.firstName']];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('p', 'm')
            ->distinct()
            ->from(Project::class, 'p')
            ->leftJoin('p.manager', 'm')
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('p.name');

        if ($this->q !== '') {
            $queryBuilder
                ->where('p.name LIKE :tq')
                ->orWhere('m.firstName LIKE :tq')
                ->orWhere('m.lastName LIKE :tq')
                ->orWhere('m.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        $queryBuilder
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $this->organization());

        if (!$this->security->isGranted(OrganizationVoter::MANAGE, $this->organization())) {
            $queryBuilder
                ->andWhere('p.manager = :manager OR (d.head IS NOT NULL AND h.person = :manager)')
                ->setParameter('manager', $this->security->getUser());
        }

        return $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $this->academicYear);
    }
}
