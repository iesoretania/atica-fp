<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Service\UserExtensionService;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Base de los listados dinámicos (Live Components): filtro de texto, filtros propios de cada listado, orden por
 * columna y paginación.
 *
 * El estado (q, page, sort, dir y filtros) NO se guarda en la URL mediante `url: true` (que solo reemplaza la entrada
 * del historial), sino con el controlador Stimulus «live-history», que añade entradas al historial al cambiar de
 * página, orden o filtro y permite volver con el botón «Atrás» del navegador (llama a la acción syncFromUrl).
 *
 * Para crear otro listado: extender esta clase, implementar authorize() y createQueryBuilder(), y escribir la plantilla
 * del componente con {% embed 'ui/live_list.html.twig' %} (ver components/Wpt/AgreementList.html.twig). Opcionalmente:
 *  - filterDefinitions(): filtros propios (select o fecha); el valor validado se obtiene con filter('clave').
 *  - sortableColumns(): columnas ordenables (clave => expresión DQL).
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

    /** Filtros propios del listado (clave => valor). Solo se tienen en cuenta los definidos en filterDefinitions(). */
    #[LiveProp(writable: true, onUpdated: 'resetPage')]
    public array $filters = [];

    /** Clave de la columna por la que se ordena (una de sortableColumns()) y sentido (asc|desc). */
    #[LiveProp(writable: true)]
    public string $sort = '';

    #[LiveProp(writable: true)]
    public string $dir = 'asc';

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
    protected TranslatorInterface $translator;

    private int $pageSize = 20;

    private ?Pagerfanta $pager = null;

    /** @var array<string, array{type: string, label: string, choices?: array<string, string>}>|null */
    private ?array $filterDefinitionsCache = null;

    #[Required]
    public function setListDependencies(
        ManagerRegistry $registry,
        Security $security,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        #[Autowire(param: 'page.size')] int|string $pageSize,
    ): void {
        $this->registry = $registry;
        $this->security = $security;
        $this->userExtensionService = $userExtensionService;
        $this->translator = $translator;
        $this->pageSize = max(1, (int) $pageSize);
    }

    /** Comprueba que la persona conectada puede ver este listado; lanza AccessDeniedException si no. */
    abstract protected function authorize(): void;

    abstract protected function createQueryBuilder(): QueryBuilder;

    /**
     * Filtros propios del listado, en el orden en que se muestran. Cada uno es
     *  - ['type' => 'select', 'label' => '...', 'choices' => ['valor' => 'texto', ...]] o
     *  - ['type' => 'date', 'label' => '...'].
     * Las etiquetas deben venir ya traducidas (this->translator).
     *
     * @return array<string, array{type: string, label: string, choices?: array<string, string>}>
     */
    public function filterDefinitions(): array
    {
        return [];
    }

    /**
     * filterDefinitions() calculada una sola vez por renderizado: las opciones de los filtros salen de consultas
     * y se necesitan varias veces (plantilla, validación de cada filtro, «chips» activos...).
     *
     * @return array<string, array{type: string, label: string, choices?: array<string, string>}>
     */
    public function definedFilters(): array
    {
        return $this->filterDefinitionsCache ??= $this->filterDefinitions();
    }

    /**
     * Columnas ordenables: clave => expresión DQL (p. ej. 'p.lastName') o lista de expresiones (apellidos y nombre).
     * El orden original de la consulta se mantiene como criterio secundario.
     *
     * @return array<string, string|list<string>>
     */
    protected function sortableColumns(): array
    {
        return [];
    }

    /** true si la consulta hace «fetch join» de colecciones (el paginador debe contar solo las entidades principales). */
    protected function fetchJoinCollection(): bool
    {
        return false;
    }

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

    /**
     * Valor de un filtro ya validado ('' si no está definido, está vacío o no es una opción/fecha válida).
     * Nunca se usa el valor recibido sin comprobarlo contra la definición.
     */
    protected function filter(string $key): string
    {
        $definition = $this->definedFilters()[$key] ?? null;
        $value = $this->filters[$key] ?? '';
        if ($definition === null || !is_scalar($value) || (string) $value === '') {
            return '';
        }
        $value = (string) $value;

        return match ($definition['type']) {
            'select' => array_key_exists($value, $definition['choices'] ?? []) ? $value : '',
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && date_create($value) !== false ? $value : '',
            default => '',
        };
    }

    /**
     * Filtros activos para mostrarlos como «chips».
     *
     * @return list<array{key: string, label: string, display: string}>
     */
    public function activeFilters(): array
    {
        $active = [];
        foreach ($this->definedFilters() as $key => $definition) {
            $value = $this->filter($key);
            if ($value !== '') {
                $active[] = [
                    'key' => $key,
                    'label' => $definition['label'],
                    'display' => $definition['type'] === 'select'
                        ? $definition['choices'][$value]
                        : (date_create($value) ?: new \DateTimeImmutable())->format('d/m/Y'),
                ];
            }
        }

        return $active;
    }

    public function hasActiveFilters(): bool
    {
        return $this->q !== '' || $this->activeFilters() !== [];
    }

    /** @return list<string> claves de los filtros definidos (las usa live-history para leer la URL) */
    public function filterKeys(): array
    {
        return array_keys($this->definedFilters());
    }

    public function getPager(): Pagerfanta
    {
        if ($this->pager === null) {
            $this->authorize();
            $queryBuilder = $this->createQueryBuilder();
            $this->applySort($queryBuilder);
            $pager = new Pagerfanta(new QueryAdapter($queryBuilder, $this->fetchJoinCollection()));
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

    private function applySort(QueryBuilder $queryBuilder): void
    {
        $columns = $this->sortableColumns();
        if (!isset($columns[$this->sort])) {
            return;
        }

        // la columna elegida pasa a ser el primer criterio y el orden original de la consulta queda como secundario
        $previous = [];
        foreach ($queryBuilder->getDQLPart('orderBy') as $orderBy) {
            foreach ($orderBy->getParts() as $part) {
                $previous[] = $part;
            }
        }
        $queryBuilder->resetDQLPart('orderBy');
        foreach ((array) $columns[$this->sort] as $expression) {
            $queryBuilder->add('orderBy', new OrderBy($expression, $this->dir === 'desc' ? 'DESC' : 'ASC'), true);
        }
        foreach ($previous as $part) {
            // cada parte ya incluye su sentido («p.lastName ASC»): se separa para no repetirlo
            if (preg_match('/^(.*\S)\s+(ASC|DESC)$/i', (string) $part, $match) === 1) {
                $queryBuilder->add('orderBy', new OrderBy($match[1], strtoupper($match[2])), true);
            } else {
                $queryBuilder->add('orderBy', new OrderBy((string) $part, 'ASC'), true);
            }
        }
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

    /** Quita el texto del filtro y todos los filtros propios. */
    #[LiveAction]
    public function clearFilters(): void
    {
        $this->q = '';
        $this->filters = [];
        $this->page = 1;
    }

    #[LiveAction]
    public function clearFilter(#[LiveArg] string $key): void
    {
        unset($this->filters[$key]);
        $this->page = 1;
    }

    /** Ordena por la columna indicada; si ya estaba ordenado por ella, invierte el sentido. */
    #[LiveAction]
    public function sortBy(#[LiveArg] string $column): void
    {
        if (!isset($this->sortableColumns()[$column])) {
            return;
        }
        $this->dir = $this->sort === $column && $this->dir === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->page = 1;
    }

    /** Restaura el estado desde la URL (botón «Atrás»/«Adelante»); no genera nuevas entradas en el historial. */
    #[LiveAction]
    public function syncFromUrl(
        #[LiveArg] string $q = '',
        #[LiveArg] int $page = 1,
        #[LiveArg] string $sort = '',
        #[LiveArg] string $dir = 'asc',
        #[LiveArg] array $filters = [],
    ): void {
        $this->q = trim($q);
        $this->page = max(1, $page);
        $this->sort = $sort;
        $this->dir = $dir === 'desc' ? 'desc' : 'asc';
        $this->filters = array_filter($filters, static fn ($v): bool => is_scalar($v) && (string) $v !== '');
    }
}
