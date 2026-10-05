<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Integration;

/**
 * Écrit et résout un média dans la bibliothèque JoliMedia par défaut.
 * Le type de $libraries n’est pas importé : ce service n’existe que si le paquet est installé.
 */
final class JoliMediaStorage
{
    public function __construct(private object $libraries)
    {
    }

    /** @return array{path: string, url: string} */
    public function store(string $filename, string $content): array
    {
        $media = $this->libraries->getDefault()->getOriginalStorage()->createMedia('builder/'.$filename, $content);

        return ['path' => $media->getPath(), 'url' => $media->getUrl()];
    }

    public function url(?string $path): string
    {
        if (null === $path || '' === $path || self::isPublic($path)) {
            return (string) $path;
        }

        $media = $this->libraries->getDefault()->getOriginalStorage()->resolve($path);

        return $media?->getUrl() ?? $path;
    }

    public static function isPublic(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
