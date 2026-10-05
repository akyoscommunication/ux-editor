<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Service;

final class BlockRenderer
{
    public function __construct(
        private BlockRegistry $registry,
        private BlockHydrator $hydrator,
    ) {
    }

    public function decorate(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $block) {
            $type = (string) ($block['type'] ?? '');
            if (!$this->registry->has($type)) {
                $out[] = [
                    'id' => (string) ($block['id'] ?? ''),
                    'type' => $type,
                    'missing' => true,
                ];
                continue;
            }

            $definition = $this->registry->get($type);
            $dto = $this->hydrator->hydrate($definition->class, $block['props'] ?? []);
            $block['definition'] = $definition;
            $block['view'] = $this->hydrator->viewProps($dto);
            $block['missing'] = false;
            if ($definition->container) {
                $slots = [];
                foreach ($block['slots'] ?? [] as $children) {
                    $slots[] = $this->decorate($children);
                }
                $block['slots'] = $slots;
            }
            $out[] = $block;
        }

        return $out;
    }
}
