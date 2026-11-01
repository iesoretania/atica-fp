<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Base de los listados dinámicos (Live Components): filtro de texto y paginación.
 *
 * El estado (q y page) NO se guarda en la URL mediante `url: true` (que solo reemplaza la entrada del historial),
 * sino con el controlador Stimulus «live-history», que añade entradas al historial al cambiar de página y permite
 * volver con el botón «Atrás» del navegador (llama a la acción syncFromUrl).
 *
 * Para crear otro listado: extender esta clase, implementar authorize() y createQueryBuilder(), y escribir la plantilla
 * del componente con {% embed 'ui/live_list.html.twig' %} (ver components/Wpt/ActivityList.html.twig).
 *
 * IMPORTANTE: authorize() se ejecuta en CADA renderizado (no solo al montar el componente, porque cada actualización
 * llega a /_components/... como una petición nueva), así que debe repetir las comprobaciones del controlador de la página.
 */
abstract class AbstractListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'resetPage')]
    public string $q = '';

    #[LiveProp(writable: true)]
    public int $page = 1;

    /** Ruta del listado sin parámetros de consulta (la usa live-history para componer la URL). Se firma con el resto de propiedades. */
    #[LiveProp]
    public string $baseUrl = '';

    /** Enlace del botón «Volver». */
    #[LiveProp]
    public string $backUrl = '';

    /** Curso académico, en los listados que se filtran por curso. */
    #[LiveProp]
    public ?AcademicYear $academicYear = null;

    protected ManagerRegistry $registry;
    protected Security $security;
    protected UserExtensionService $userExtensionService;

    private int $pageSize = 20;

    private ?Pagerfanta $pager = null;

    #[Required]
    public function setListDependencies(
        ManagerRegistry $registry,
        Security $security,
        UserExtensionService $userExtensionService,
        #[Autowire(param: 'page.size')] int|string $pageSize,
    ): void {
        $this->registry = $registry;
        $this->security = $security;
        $this->userExtensionService = $userExtensionService;
        $this->pageSize = max(1, (int) $pageSize);
    }

    /** Comprueba que la persona conectada puede ver este listado; lanza AccessDeniedException si no. */
    abstract protected function authorize(): void;

    abstract protected function createQueryBuilder(): QueryBuilder;

    protected function organization(): Organization
    {
        return $this->userExtensionService->getCurrentOrganization();
    }

    protected function newQueryBuilder(): QueryBuilder
    {
        return $this->registry->getManager()->createQueryBuilder();
    }

    protected function denyUnlessGranted(string $attribute, mixed $subject = null): void
    {
        if (!$this->security->isGranted($attribute, $subject)) {
            throw new AccessDeniedException();
        }
    }

    /** El curso académico del listado debe ser del centro actual. */
    protected function denyUnlessAcademicYearIsCurrentOrganizations(): void
    {
        if (!$this->academicYear instanceof AcademicYear || $this->academicYear->getOrganization() !== $this->organization()) {
            throw new AccessDeniedException();
        }
    }

    public function getPager(): Pagerfanta
    {
        if ($this->pager === null) {
            $this->authorize();
            $pager = new Pagerfanta(new QueryAdapter($this->createQueryBuilder(), false));
            $pager->setMaxPerPage($this->pageSize);
            try {
                $pager->setCurrentPage(max(1, $this->page));
            } catch (OutOfRangeCurrentPageException) {
                $pager->setCurrentPage(1);
                $this->page = 1;
            }
            $this->pager = $pager;
        }

        return $this->pager;
    }

    public function resetPage(): void
    {
        $this->page = 1;
    }

    #[LiveAction]
    public function setPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
    }

    #[LiveAction]
    public function clearQuery(): void
    {
        $this->q = '';
        $this->page = 1;
    }

    /** Restaura el estado desde la URL (botón «Atrás»/«Adelante»); no genera nuevas entradas en el historial. */
    #[LiveAction]
    public function syncFromUrl(#[LiveArg] string $q = '', #[LiveArg] int $page = 1): void
    {
        $this->q = trim($q);
        $this->page = max(1, $page);
    }
}
