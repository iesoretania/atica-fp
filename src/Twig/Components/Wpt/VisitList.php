<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\Edu\Teacher;
use App\Entity\WptModule\Contact;
use App\Security\WptModule\OrganizationVoter as WptOrganizationVoter;
use App\Security\WptModule\VisitVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Visitas a centros de trabajo de un docente de FCT, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:VisitList', template: 'components/Wpt/VisitList.html.twig')]
final class VisitList extends AbstractListComponent
{
    #[LiveProp]
    public Teacher $teacher;

    public function allowNew(): bool
    {
        return $this->security->isGranted(WptOrganizationVoter::WPT_CREATE_VISIT, $this->organization());
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(WptOrganizationVoter::WPT_ACCESS_VISIT, $this->organization());

        // probar si tiene acceso a las visitas de este usuario
        $visit = (new Contact())->setTeacher($this->teacher)->setDateTime(new \DateTime());
        $this->denyUnlessGranted(VisitVoter::ACCESS, $visit);
    }

    public function filterDefinitions(): array
    {
        return [
            'from' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_from', [], 'general')],
            'to' => ['type' => 'date', 'label' => $this->translator->trans('filters.date_to', [], 'general')],
        ];
    }

    protected function sortableColumns(): array
    {
        return ['datetime' => 'v.dateTime'];
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->newQueryBuilder()
            ->select('v')
            ->distinct()
            ->addSelect('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->from(Contact::class, 'v')
            ->join('v.workcenter', 'w')
            ->join('w.company', 'c')
            ->leftJoin('v.agreements', 'a')
            ->leftJoin('v.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->addOrderBy('v.dateTime', 'DESC');

        if ($this->q !== '') {
            $queryBuilder
                ->orWhere('a.name LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->setParameter('tq', '%' . $this->q . '%');
        }

        if (($from = $this->filter('from')) !== '') {
            $queryBuilder->andWhere('v.dateTime >= :filter_from')->setParameter('filter_from', new \DateTime($from . ' 00:00:00'));
        }
        if (($to = $this->filter('to')) !== '') {
            $queryBuilder->andWhere('v.dateTime < :filter_to')->setParameter('filter_to', (new \DateTime($to . ' 00:00:00'))->modify('+1 day'));
        }

        return $queryBuilder
            ->andWhere('v.teacher = :teacher')
            ->setParameter('teacher', $this->teacher);
    }
}
