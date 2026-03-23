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
        $children = [];
        foreach ($data['children'] ?? [] as $child) {
            if (!\is_array($child)) {
                continue;
            }
            $children[] = $this->hydrate($child, Component::class, $autoload);
        }

        return (new $className())
            ->setId($data['id'] ?? '')
            ->setType($data['type'] ?? null)
            ->setChildren($children)
            ->setOrder($data['order'] ?? null)
            ->setData($data['data'] ?? [])
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
            'children' => array_map(
                fn(Component $child) => $this->dehydrate($child),
                $object->getChildren() ?? []
            ),
            'order' => $object->getOrder(),
        ];
    }
}
