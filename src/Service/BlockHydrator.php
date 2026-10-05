<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

final class BlockHydrator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function defaults(string $class): array
    {
        return $this->extract(new $class());
    }

    public function hydrate(string $class, array $props): object
    {
        $dto = new $class();
        $reflection = new \ReflectionClass($class);
        while ($reflection) {
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $reflection->getName() || !$property->isPublic() || $property->isStatic()) {
                    continue;
                }
                if (!\array_key_exists($property->getName(), $props)) {
                    continue;
                }
                $property->setValue($dto, $this->denormalize($property, $props[$property->getName()]));
            }
            $reflection = $reflection->getParentClass() ?: null;
        }

        return $dto;
    }

    public function extract(object $dto): array
    {
        $out = [];
        $reflection = new \ReflectionClass($dto);
        while ($reflection) {
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $reflection->getName() || !$property->isPublic() || $property->isStatic()) {
                    continue;
                }
                if (!$property->getAttributes(FormField::class)) {
                    continue;
                }
                $out[$property->getName()] = $this->normalize($property->getValue($dto));
            }
            $reflection = $reflection->getParentClass() ?: null;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function viewProps(object $dto): array
    {
        $props = [];
        foreach ((new \ReflectionClass($dto))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            if (!$property->getAttributes(FormField::class) && !$property->getAttributes(LiveProp::class)) {
                continue;
            }
            $props[$property->getName()] = $property->getValue($dto);
        }

        return $props;
    }

    private function normalize(mixed $value): mixed
    {
        if (\is_object($value) && $this->isEntity($value)) {
            return $value->getId();
        }
        if (\is_object($value)) {
            return $this->extract($value);
        }
        if (\is_array($value)) {
            return array_map(fn (mixed $item): mixed => \is_object($item) ? $this->normalize($item) : $item, $value);
        }

        return $value;
    }

    private function denormalize(\ReflectionProperty $property, mixed $value): mixed
    {
        $entryClass = $this->entryClass($property);
        if (\is_array($value) && null !== $entryClass) {
            return array_map(
                fn (mixed $item): mixed => \is_array($item) ? $this->hydrate($entryClass, $item) : $item,
                $value,
            );
        }

        $type = $property->getType();
        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        if (null !== $typeName && class_exists($typeName) && $this->isEntityClass($typeName)) {
            if (null === $value || '' === $value) {
                return null;
            }

            return $this->entityManager->getRepository($typeName)->find($value);
        }
        if (null !== $typeName && $this->isFormType($typeName)) {
            if (\is_array($value)) {
                return $this->hydrate($typeName, $value);
            }
            if (null === $value) {
                return ($type?->allowsNull() ?? true) ? null : new $typeName();
            }
        }
        if ('string' === $typeName && null === $value && !($type?->allowsNull() ?? true)) {
            return '';
        }
        if ('int' === $typeName && null !== $value && '' !== $value) {
            return (int) $value;
        }
        if ('bool' === $typeName && null !== $value) {
            return (bool) $value;
        }

        return $value;
    }

    private function entryClass(\ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(FormField::class);
        if (!$attributes) {
            return null;
        }
        $type = $attributes[0]->newInstance()->options['entry_type'] ?? null;

        return \is_string($type) && class_exists($type) ? $type : null;
    }

    private function isEntity(object $value): bool
    {
        return $this->isEntityClass($value::class) && method_exists($value, 'getId');
    }

    private function isEntityClass(string $class): bool
    {
        return class_exists($class) && (new \ReflectionClass($class))->getAttributes(Entity::class) !== [];
    }

    private function isFormType(string $class): bool
    {
        return class_exists($class) && (new \ReflectionClass($class))->getAttributes(AsFormType::class) !== [];
    }
}
