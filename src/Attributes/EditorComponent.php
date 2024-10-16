<?php

namespace Akyos\UXEditor\Attributes;

use App\Model\Category;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class EditorComponent
{
    public function __construct(
        public string $icon,
        public string $label,
        /** @var Category[] $categories */
        // @TODO: make it translatable
        public array $categories,
        public bool $isContainer = false,
    ) {}
}
