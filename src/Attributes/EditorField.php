<?php

namespace Akyos\UXEditor\Attributes;

use Akyos\UXEditor\Model\Category;
use Symfony\Component\Form\Extension\Core\Type\TextType;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class EditorField
{
    public function __construct(
        public ?string $component = null,
        public ?string $type = null,
        public array $typeOpts = [],
        public string $label = '',
        public Category|string|null $category = null,
    ) {}
}
