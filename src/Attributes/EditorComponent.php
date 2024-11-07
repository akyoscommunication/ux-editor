<?php

namespace Akyos\UXEditor\Attributes;

use Akyos\UXEditor\Model\Category;
use Symfony\Component\Form\FormTypeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class EditorComponent
{
    public function __construct(
        public string $formType,
        public string $icon,
        public string $label,
        /** @var Category[]|string[] $categories */
        // @TODO: make it translatable
        public array $categories,
        public bool $isContainer = false,
    ) {}

    public function serviceConfig(): array
    {
        return [
            'icon' => $this->icon,
            'label' => $this->label,
            'categories' => array_map(fn ($category) => ($category instanceof Category) ? $category->getName() : $category, $this->categories),
            'isContainer' => $this->isContainer,
        ];
    }
}
