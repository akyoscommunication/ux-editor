<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Hydration\DataHydrationExtension;
use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('UX:Editor:ComponentLayer', template: '@UXEditor/components/ComponentLayer.html.twig')]
final class ComponentLayer extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, updateFromParent: true)]
    public Component $component;

    #[LiveProp(updateFromParent: true)]
    public $keyOfComponent;

    public function __construct(
        private EditorService $editorService,
        private DataHydrationExtension $dataHydrationExtension
    ){}
}
