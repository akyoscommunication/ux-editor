<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Model;

final class BlockDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly string $label,
        public readonly string $category,
        public readonly string $icon,
        public readonly bool $container,
        public readonly string $component,
        public readonly string $template,
        public readonly bool $live,
    ) {
    }
}
