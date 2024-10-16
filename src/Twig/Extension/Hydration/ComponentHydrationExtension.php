<?php

namespace Akyos\UXEditor\Twig\Extension\Hydration;

use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Service\EditorService;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

class ComponentHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(
        private DataHydrationExtension $dataHydrationExtension,
        private EditorService $editorService,
    ){}

    public function supports(string $className): bool
    {
        return $className === Component::class;
    }

    public function hydrate(mixed $data, string $className = Component::class, bool $autoload = false): object
    {
        $component = (new $className)
            ->setId($data['id'])
            ->setType($data['type'])
            ->setChildren($data['children'])
            ->setOrder($data['order'])
        ;

        $datas = $data['data'] ?? [];
        foreach ($datas as $key => $value) {
            $d = $this->dataHydrationExtension->hydrate(['name' => $key, 'value' => $value]);
            if ($autoload) {
                $metadata = $this->editorService->getDataFieldMetadata($component, $d);
                $d->setValue($metadata['realValue']);
            }
            $datas[$key] = $d;
        }

        return $component->setData($datas);
    }

    /**
     * @param Component $object
     */
    public function dehydrate(object $object): array
    {
        return [
            'id' => $object->getId(),
            'type' => $object->getType(),
            'data' => array_map(
                fn($data) => $this->dataHydrationExtension->dehydrate($data),
                $object->getData()
            ),
            'children' => $object->getChildren(),
            'order' => $object->getOrder(),
        ];
    }
}
