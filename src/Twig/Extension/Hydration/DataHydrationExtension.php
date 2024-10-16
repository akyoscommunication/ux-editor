<?php

namespace Akyos\UXEditor\Twig\Extension\Hydration;

use Akyos\UXEditor\Model\Data;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

class DataHydrationExtension implements HydrationExtensionInterface
{
    public function supports(string $className): bool
    {
        return $className === Data::class;
    }

    public function hydrate(mixed $data, string $className = Data::class): object
    {
        return new $className($data['name'], $data['value']);
    }

    public function dehydrate(object $object): mixed
    {
        if (is_array($object->getValue())) {
            return array_map(fn($value) => $value instanceof Data ? $this->dehydrate($value) : $value, $object->getValue());
        }

        return $object->getValue();
    }
}