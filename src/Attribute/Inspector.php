<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Attribute;

/**
 * Places a form field in an inspector tab and column.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Inspector
{
    public function __construct(
        public readonly ?string $tab = null,
        public readonly int $column = 1,
        public readonly int $span = 1,
    ) {
    }
}
