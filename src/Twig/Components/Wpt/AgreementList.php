<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Entity\WptModule\Shift;
use App\Repository\WptModule\AgreementEnrollmentRepository;
use App\Security\WptModule\ShiftVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/** Listado de acuerdos de colaboración de una convocatoria de FCT, con filtro y paginación dinámicos. */
#[AsLiveComponent('Wpt:AgreementList', template: 'components/Wpt/AgreementList.html.twig')]
final class AgreementList extends AbstractListComponent
{
    #[LiveProp]
    public Shift $shift;

    public function __construct(private readonly AgreementEnrollmentRepository $repository)
    {
    }

    protected function authorize(): void
    {
        $this->denyUnlessGranted(ShiftVoter::MANAGE, $this->shift);
        if ($this->shift->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->repository->findByShiftAndFilter($this->shift, $this->q !== '' ? $this->q : null);
    }
}
