<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Service;

use Builder\PageBuilderBundle\Model\BlockDefinition;

final class BlockRegistry
{
    /** @var array<string, BlockDefinition> */
    private array $definitions;

    public function __construct(
        array $definitions,
    ) {
        $this->definitions = [];
        foreach ($definitions as $name => $data) {
            $this->definitions[$name] = new BlockDefinition(
                name: $data['name'],
                class: $data['class'],
                label: $data['label'],
                category: $data['category'],
                icon: $data['icon'],
                container: $data['container'],
                component: $data['component'],
                template: $data['template'],
                live: $data['live'],
            );
        }
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[$name]);
    }

    public function get(string $name): BlockDefinition
    {
        return $this->definitions[$name] ?? throw new \InvalidArgumentException(sprintf('Bloc "%s" inconnu.', $name));
    }

    /** @return list<BlockDefinition> */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    /** @return array<string, list<BlockDefinition>> */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->definitions as $definition) {
            $groups[$definition->category][] = $definition;
        }
        ksort($groups);
        foreach ($groups as &$items) {
            usort($items, static fn (BlockDefinition $a, BlockDefinition $b): int => strcmp($a->label, $b->label));
        }

        return $groups;
    }
}
