<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * live_list_state(): estado inicial de un listado dinámico (Live Component) a partir de la petición: URL base sin
 * página ni filtros, texto del filtro, página, orden y filtros (parámetros f_nombre). Lo usan las páginas que incluyen
 * un componente que extiende AbstractListComponent.
 */
final class LiveListExtension extends AbstractExtension
{
    public const FILTER_PREFIX = 'f_';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('live_list_state', $this->state(...))];
    }

    /**
     * @return array{baseUrl: string, q: string, page: int, sort: string, dir: string, filters: array<string, string>}
     */
    public function state(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return ['baseUrl' => '', 'q' => '', 'page' => 1, 'sort' => '', 'dir' => 'asc', 'filters' => []];
        }

        $routeParams = (array) $request->attributes->get('_route_params', []);
        $routePage = (int) ($routeParams['page'] ?? 1);
        unset($routeParams['page']);

        $filters = [];
        foreach ($request->query->all() as $key => $value) {
            if (str_starts_with((string) $key, self::FILTER_PREFIX) && is_scalar($value)) {
                $filters[substr((string) $key, strlen(self::FILTER_PREFIX))] = (string) $value;
            }
        }

        return [
            'baseUrl' => $this->urlGenerator->generate((string) $request->attributes->get('_route'), $routeParams),
            'q' => trim((string) $request->query->get('q')),
            'page' => max(1, $request->query->getInt('page', $routePage)),
            'sort' => (string) $request->query->get('sort', ''),
            'dir' => $request->query->get('dir') === 'desc' ? 'desc' : 'asc',
            'filters' => $filters,
        ];
    }
}
