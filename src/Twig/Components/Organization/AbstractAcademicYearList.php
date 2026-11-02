<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Security\Edu\AcademicYearVoter;
use App\Twig\Components\AbstractListComponent;

/**
 * Base de los listados de datos del centro que pertenecen a un curso académico (departamentos, enseñanzas, grupos...).
 * Se repite en cada renderizado lo que comprueban los controladores: el curso académico debe ser del centro actual y
 * la persona conectada debe poder gestionarlo.
 */
abstract class AbstractAcademicYearList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessAcademicYearIsCurrentOrganizations();
        $this->denyUnlessGranted(AcademicYearVoter::MANAGE, $this->academicYear);
    }
}
