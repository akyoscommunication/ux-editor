<?php

namespace Akyos\UXEditor\Twig\Runtime;

use Akyos\UXEditor\Model\Content;
use Akyos\UXEditor\Service\EditorService;
use Akyos\UXEditor\Twig\Extension\Hydration\ContentHydrationExtension;
use Twig\Extension\RuntimeExtensionInterface;

class UXEditorExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ContentHydrationExtension $contentHydrationExtension,
        private EditorService $editorService
    ){}

    public function toEditor(?string $data, bool $autoload = true): Content
    {
        if (null === $data || '' === $data) {
            return new Content();
        }

        return $this->contentHydrationExtension->hydrate(json_decode($data, true), autoload: $autoload);
    }

    public function toEditorSerialized(Content $content): string
    {
        return json_encode($this->contentHydrationExtension->dehydrate($content));
    }

    public function filterAllowedComponents(Content $content, array $allowed_components = []): Content
    {
        if (count($allowed_components) > 0) {
            $allowed_components = array_map(
                fn($component) => $this->editorService->getTwigName($component),
                $allowed_components
            );
            $content
                ->setComponents(
                    array_filter(
                        $content->getComponents(),
                        fn($component) => in_array($component->getType(), $allowed_components)
                    )
                )
            ;
        }

        return $content;
    }
}
