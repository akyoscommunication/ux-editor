<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Controller;

use Builder\PageBuilderBundle\Integration\JoliMediaStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MediaController extends AbstractController
{
    private const MAX_BYTES = 5_000_000;

    public function __construct(private ?JoliMediaStorage $joli = null)
    {
    }

    #[Route('/_builder/media', name: 'page_builder_media', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('builder_media', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return new JsonResponse(['error' => 'Jeton invalide.'], 403);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Fichier manquant.'], 422);
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return new JsonResponse(['error' => 'Image trop lourde (5 Mo max).'], 422);
        }

        $mime = (string) $file->getMimeType();
        if (!\in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return new JsonResponse(['error' => 'Formats acceptés : JPEG, PNG, GIF, WebP.'], 422);
        }

        $extension = $file->guessExtension() ?: 'bin';
        $name = bin2hex(random_bytes(8)).'.'.$extension;
        if (null !== $this->joli) {
            return new JsonResponse($this->joli->store($name, $file->getContent()));
        }

        $directory = $this->getParameter('kernel.project_dir').'/public/uploads/builder';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return new JsonResponse(['error' => 'Dossier d’upload inaccessible.'], 500);
        }
        $file->move($directory, $name);

        return new JsonResponse(['path' => '/uploads/builder/'.$name]);
    }
}
