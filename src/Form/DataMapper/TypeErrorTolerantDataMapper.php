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

namespace App\Form\DataMapper;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

/**
 * Mapeador de datos que convierte en un error del campo los fallos de tipo al asignar su valor a la entidad.
 *
 * Si se envía vacío un campo obligatorio cuya propiedad no admite nulos, el formulario intenta asignar null
 * y PropertyAccessor lanza una excepción ("Expected argument of type string, null given") que acaba en un
 * error 500. Con este mapeador el campo queda marcado como no válido y el formulario se muestra de nuevo
 * con el error, sin modificar el comportamiento de los valores que sí se pueden asignar.
 */
class TypeErrorTolerantDataMapper implements DataMapperInterface
{
    public function __construct(private readonly DataMapperInterface $inner)
    {
    }

    /**
     * @param \Traversable<FormInterface> $forms
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $this->inner->mapDataToForms($viewData, $forms);
    }

    /**
     * @param \Traversable<FormInterface> $forms
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        foreach ($forms as $name => $form) {
            try {
                $this->inner->mapFormsToData(new \ArrayIterator([$name => $form]), $viewData);
            } catch (InvalidArgumentException|UnexpectedTypeException|\TypeError) {
                $this->markAsInvalid($form);
            }
        }
    }

    private function markAsInvalid(FormInterface $form): void
    {
        $config = $form->getConfig();
        $message = $config->getOption('invalid_message');
        $form->addError(new FormError(
            $message,
            $message,
            $config->getOption('invalid_message_parameters')
        ));
    }
}
