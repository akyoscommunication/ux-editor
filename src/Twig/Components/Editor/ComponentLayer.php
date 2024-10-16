<?php

namespace Akyos\UXEditor\Twig\Components\Editor;

use Akyos\UXEditor\Service\EditorService;
use Akyos\UXEditor\Model\Editor\Component;
use Akyos\UXEditor\Form\Type\ComponentType;
use Akyos\UXEditor\Attributes\EditorComponent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Akyos\UXEditor\Twig\Extension\Hydration\DataHydrationExtension;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent]
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
