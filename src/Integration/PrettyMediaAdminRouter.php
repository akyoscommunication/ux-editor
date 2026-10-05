<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Integration;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use JoliCode\MediaBundle\Bridge\EasyAdmin\Controller\MediaAdminController;
use JoliCode\MediaBundle\Bridge\EasyAdmin\Router\MediaAdminRouter;

/**
 * Le rendu live du builder n’a pas de contexte EasyAdmin, donc JoliMedia
 * retombe sur des routes legacy absentes quand le dashboard utilise les
 * pretty URLs. On prend le premier dashboard, comme EasyAdmin sans contexte.
 */
readonly class PrettyMediaAdminRouter extends MediaAdminRouter
{
    public function __construct(
        AdminUrlGeneratorInterface $adminUrlGenerator,
        private AdminRouteGenerator $routes,
        AdminContextProviderInterface $adminContextProvider,
    ) {
        parent::__construct($adminUrlGenerator, $routes, $adminContextProvider);
    }

    public function getRouteName(string $action): string
    {
        $name = parent::getRouteName($action);
        if (!str_starts_with($name, 'joli_media_easy_admin_')) {
            return $name;
        }

        return $this->routes->findRouteName(null, MediaAdminController::class, $action) ?? $name;
    }
}
