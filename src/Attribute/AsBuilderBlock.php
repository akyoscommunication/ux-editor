<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsBuilderBlock
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $category = 'Général',
        public readonly string $icon = 'heroicons:squares-2x2',
        public readonly bool $container = false,
    ) {
    }
}
