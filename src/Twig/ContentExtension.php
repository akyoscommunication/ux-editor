<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Twig;

use Builder\PageBuilderBundle\Integration\JoliMediaStorage;
use Builder\PageBuilderBundle\Model\Margin;
use Builder\PageBuilderBundle\Service\BlockRenderer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;

final class ContentExtension
{
    public function __construct(
        private BlockRenderer $renderer,
        #[Autowire('%page_builder.joli_media%')]
        private bool $joliMedia,
        private ?JoliMediaStorage $joli = null,
    ) {
    }

    #[AsTwigFunction('builder_content', isSafe: ['html'])]
    public function content(Environment $environment, object $entity): string
    {
        $content = $this->read($entity);
        if (!\is_array($content) || !\is_array($content['blocks'] ?? null)) {
            return '';
        }

        return $environment->render('@PageBuilder/content.html.twig', [
            'blocks' => $this->renderer->decorate($content['blocks']),
        ]);
    }

    private function read(object $entity): mixed
    {
        $reflection = new \ReflectionClass($entity);
        if ($reflection->hasProperty('content')) {
            $property = $reflection->getProperty('content');
            if ($property->isPublic() && !$property->isStatic()) {
                return $property->getValue($entity);
            }
        }
        if ($reflection->hasMethod('getContent')) {
            $method = $reflection->getMethod('getContent');
            if ($method->isPublic() && 0 === $method->getNumberOfRequiredParameters()) {
                return $entity->getContent();
            }
        }

        return null;
    }

    #[AsTwigFunction('builder_media', isSafe: ['html'])]
    public function media(Environment $environment, ?string $path, string $alt = '', string $class = ''): string
    {
        if (null === $path || '' === $path) {
            return '';
        }
        if (!$this->joliMedia || JoliMediaStorage::isPublic($path)) {
            return sprintf(
                '<img src="%s" alt="%s" class="%s">',
                htmlspecialchars($path, \ENT_QUOTES),
                htmlspecialchars($alt, \ENT_QUOTES),
                htmlspecialchars($class, \ENT_QUOTES),
            );
        }

        return $environment->render('@PageBuilder/media.html.twig', [
            'path' => $path,
            'alt' => $alt,
            'class' => $class,
        ]);
    }

    #[AsTwigFunction('builder_margin_style')]
    public function marginStyle(mixed $margin): string
    {
        return Margin::style($margin);
    }

    #[AsTwigFunction('builder_media_url')]
    public function mediaUrl(?string $path): string
    {
        if (null === $path || '' === $path) {
            return '';
        }

        return $this->joli?->url($path) ?? $path;
    }
}
