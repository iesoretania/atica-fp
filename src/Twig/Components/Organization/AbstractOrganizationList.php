<?php

declare(strict_types=1);

namespace App\Twig\Components\Organization;

use App\Security\OrganizationVoter;
use App\Twig\Components\AbstractListComponent;

/**
 * Base de los listados de datos que pertenecen al centro y no a un curso académico (escalas de valoración,
 * plantillas de informe, encuestas...). Las consultas deben filtrar siempre por el centro actual.
 */
abstract class AbstractOrganizationList extends AbstractListComponent
{
    protected function authorize(): void
    {
        $this->denyUnlessGranted(OrganizationVoter::MANAGE, $this->organization());
    }
}
