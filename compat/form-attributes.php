<?php

declare(strict_types=1);

namespace Symfony\Component\Form\Attribute;

if (!class_exists(FormField::class, false)) {
    #[\Attribute(\Attribute::TARGET_PROPERTY)]
    class FormField
    {
        /** @param array<string, mixed> $options */
        public function __construct(
            public ?string $type = null,
            public array $options = [],
            public ?string $name = null,
        ) {
        }
    }
}

if (!class_exists(AsFormType::class, false)) {
    #[\Attribute(\Attribute::TARGET_CLASS)]
    class AsFormType
    {
        /** @param array<string, mixed> $options */
        public function __construct(public array $options = [])
        {
        }
    }
}
