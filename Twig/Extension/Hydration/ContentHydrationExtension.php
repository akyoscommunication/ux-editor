<?php

namespace Akyos\UXEditor\Twig\Extension\Hydration;

use Akyos\UXEditor\Model\Content;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

class ContentHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(
        private ComponentHydrationExtension $componentHydrationExtension
    ){}

    public function supports(string $className): bool
    {
        return $className === Content::class;
    }

    public function hydrate(mixed $data, string $className = Content::class, bool $autoload = false): object
    {
        return (new $className())
            ->setComponents(
                array_map(
                    fn($component) => $this->componentHydrationExtension->hydrate($component, autoload: $autoload),
                    $data['components'] ?? []
                )
            )
        ;
    }

    public function dehydrate(object $object): array
    {
        return [
            'components' => array_map(
                fn($component) => $this->componentHydrationExtension->dehydrate($component),
                $object->getComponents()
            )
        ];
    }
}
