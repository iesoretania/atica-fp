<?php

declare(strict_types=1);

namespace App\Twig\Components\Wpt;

use App\Controller\WptModule\TrackingController;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WptModule\GroupRepository as WptGroupRepository;
use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;
use Doctrine\ORM\QueryBuilder;

/**
 * Listados de acuerdos del alumnado de FCT visibles para la persona conectada, por curso académico (seguimiento e
 * informe final). Comparten consulta; cada uno comprueba su propio permiso en authorize().
 */
abstract class AbstractAgreementEnrollmentList extends AbstractListComponent
{
    public function __construct(
        private readonly WptGroupRepository $groupRepository,
        private readonly TeacherRepository $teacherRepository,
    ) {
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return TrackingController::generateAgreementQueryBuilder(
            $this->groupRepository,
            $this->teacherRepository,
            $this->academicYear,
            $this->newQueryBuilder(),
            $this->security->getUser(),
            $this->security->isGranted(OrganizationVoter::MANAGE, $this->organization()),
            $this->q !== '' ? $this->q : null,
        );
    }
}
