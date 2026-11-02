<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Convierte la entidad (o entidades, si el campo es múltiple) de un campo de búsqueda remota en el par «identificador =>
 * texto» que se dibuja en el desplegable, y al revés. El navegador solo envía identificadores.
 */
final class RemoteEntityTransformer implements DataTransformerInterface
{
    private readonly PropertyAccessor $accessor;

    public function __construct(
        private readonly ObjectManager $em,
        private readonly string $className,
        private readonly ?string $textProperty,
        private readonly string $primaryKey,
        private readonly bool $multiple,
    ) {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /** @return array<string, string> */
    public function transform(mixed $value): mixed
    {
        if (empty($value)) {
            return [];
        }

        $entities = $this->multiple ? $value : [$value];
        $data = [];
        foreach ($entities as $entity) {
            $text = $this->textProperty === null
                ? (string) $entity
                : (string) $this->accessor->getValue($entity, $this->textProperty);
            $data[(string) $this->accessor->getValue($entity, $this->primaryKey)] = $text;
        }

        return $data;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if ($this->multiple) {
            return $this->reverseMultiple($value);
        }

        if (empty($value)) {
            return null;
        }

        $entity = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->className, 'entity')
            ->where('entity.' . $this->primaryKey . ' = :id')
            ->setParameter('id', $value)
            ->getQuery()
            ->getOneOrNullResult();

        // llega si el formulario envía un identificador que no existe
        if ($entity === null) {
            throw new TransformationFailedException(sprintf('The choice "%s" does not exist or is not unique', is_scalar($value) ? $value : gettype($value)));
        }

        return $entity;
    }

    /** @return list<object> */
    private function reverseMultiple(mixed $values): array
    {
        if (!is_array($values) || $values === []) {
            return [];
        }

        $values = array_values(array_unique($values));
        $entities = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($this->className, 'entity')
            ->where('entity.' . $this->primaryKey . ' IN (:ids)')
            ->setParameter('ids', $values)
            ->getQuery()
            ->getResult();

        if (count($entities) !== count($values)) {
            throw new TransformationFailedException('One or more id values are invalid');
        }

        return $entities;
    }
}
