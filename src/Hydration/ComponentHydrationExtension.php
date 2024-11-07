<?php

namespace Akyos\UXEditor\Hydration;

use Akyos\UXEditor\Model\Component;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

class ComponentHydrationExtension implements HydrationExtensionInterface
{
    public function supports(string $className): bool
    {
        return $className === Component::class;
    }

    public function hydrate(mixed $data, string $className = Component::class, bool $autoload = false): object
    {
        return (new $className)
            ->setId($data['id'])
            ->setType($data['type'])
            ->setChildren($data['children'])
            ->setOrder($data['order'])
            ->setData($data['data'])
        ;
    }

    /**
     * @param Component $object
     */
    public function dehydrate(object $object): array
    {
        return [
            'id' => $object->getId(),
            'type' => $object->getType(),
            'data' => $object->getData(),
            'children' => $object->getChildren(),
            'order' => $object->getOrder(),
        ];
    }
}
