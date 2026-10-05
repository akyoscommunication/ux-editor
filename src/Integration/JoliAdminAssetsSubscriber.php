<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Integration;

use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use JoliCode\MediaBundle\Bridge\EasyAdmin\Asset\AssetRegistrar;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MediaChoiceField charge le JS de la médiathèque. Le champ contenu du builder
 * ne passe pas par ce field, donc sans ça la modale s’ouvre vide.
 */
final class JoliAdminAssetsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [BeforeCrudActionEvent::class => 'register'];
    }

    public function register(BeforeCrudActionEvent $event): void
    {
        (new AssetRegistrar())->registerOn($event->getAdminContext());
    }
}
