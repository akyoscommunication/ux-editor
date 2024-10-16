<?php

namespace Akyos\UXEditor\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UXEditorExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('editor', [UXEditorExtensionRuntime::class, 'toEditor']),
            new TwigFilter('editor_serialized', [UXEditorExtensionRuntime::class, 'toEditorSerialized']),
            new TwigFilter('filter_allowed_components', [UXEditorExtensionRuntime::class, 'filterAllowedComponents']),
        ];
    }
}
