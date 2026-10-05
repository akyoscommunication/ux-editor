<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Attribute;

/**
 * Shows the field only when every condition matches.
 *
 * ponytail: equality / in-list / not-empty, combined with AND. No callbacks —
 * attribute options cannot hold closures. A form type extension on the DTO
 * covers anything richer.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DependsOn
{
    /** @param list<scalar|null> $values */
    public function __construct(
        public readonly string $field,
        public readonly array $values = [],
        public readonly bool $notEmpty = false,
    ) {
    }
}
