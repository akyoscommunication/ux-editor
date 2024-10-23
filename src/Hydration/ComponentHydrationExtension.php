<?php

namespace Akyos\UXEditor\Hydration;

use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Model\Data;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

class ComponentHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(
        private readonly DataHydrationExtension $dataHydrationExtension,
    ){}

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
            ->setData($this->hydrateDataRecursive($data['data']))
        ;
    }

    public function hydrateDataRecursive(array $data): array
    {
        $return = [];

        foreach ($data as $k => $value) {
            // if key is numeric, it's live collection
            if (is_numeric($k) && isset($value['data'])) {
                $return[] = ['data' => $this->hydrateDataRecursive($value['data'])];
                continue;
            }

            if (is_array($value)) {
                $return[$k] = $this->dataHydrationExtension->hydrate(['name' => $k, 'value' => $this->hydrateDataRecursive($value)]);
            } else {
                $return[$k] = $this->dataHydrationExtension->hydrate(['name' => $k, 'value' => $value]);
            }
        }

        return $return;
    }

    /**
     * @param Component $object
     */
    public function dehydrate(object $object): array
    {
        return [
            'id' => $object->getId(),
            'type' => $object->getType(),
            'data' => $this->dehydrateRecursive($object->getData()),
            'children' => $object->getChildren(),
            'order' => $object->getOrder(),
        ];
    }

    private function dehydrateRecursive(array $datas): array
    {
        $return = [];
        foreach ($datas as $key => $data) {
            if ($data instanceof Data && is_array($data->getValue())) {
                $return[$data->getName()] = $this->dehydrateRecursive($data->getValue());
                continue;
            }

            if (is_array($data) && isset($data['data'])) {
                $return[$key] = ['data' => $this->dehydrateRecursive($data['data'])];
                continue;
            }

            if ($data instanceof Data) {
                $return[$data->getName()] = $this->dataHydrationExtension->dehydrate($data);
                continue;
            }

            $return[$key] = $data;
        }

        return $return;
    }
}
