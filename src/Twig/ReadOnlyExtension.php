<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace App\Twig;

use App\EventSubscriber\LockedTrainingProgramSubscriber;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Función `is_read_only()`: ¿se está consultando la pantalla en solo lectura (plan de formación bloqueado)? Sirve para
 * ocultar los botones que modifican datos.
 */
class ReadOnlyExtension extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('is_read_only', $this->isReadOnly(...))];
    }

    public function isReadOnly(): bool
    {
        return (bool) $this->requestStack->getCurrentRequest()?->attributes->get(LockedTrainingProgramSubscriber::ATTRIBUTE, false);
    }
}
