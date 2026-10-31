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

namespace App\Form\Extension;

use App\EventSubscriber\LockedTrainingProgramSubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Los formularios de una petición marcada como solo lectura (plan de formación bloqueado de la FFEOE) se crean
 * desactivados, salvo que el controlador indique otra cosa.
 */
class ReadOnlyFormExtension extends AbstractTypeExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'disabled',
            fn(Options $options): bool => (bool) $this->requestStack->getCurrentRequest()?->attributes->get(LockedTrainingProgramSubscriber::ATTRIBUTE, false)
        );
    }
}
