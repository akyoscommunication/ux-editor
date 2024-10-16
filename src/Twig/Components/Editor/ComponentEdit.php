<?php

namespace Akyos\UXEditor\Twig\Components\Editor;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Form\Type\ComponentType;
use Akyos\UXEditor\Hydration\DataHydrationExtension;
use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class ComponentEdit extends AbstractController
{
    use DefaultActionTrait, ComponentWithFormTrait, ComponentToolsTrait, LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'c', updateFromParent: true)]
    public Component $component;

    #[LiveProp(updateFromParent: true)]
    public $keyOfComponent;

    #[LiveProp(writable: true)]
    public $currentFieldsFilter;

    public function __construct(
        private EditorService $editorService,
        private DataHydrationExtension $dataHydrationExtension
    ){}

    public function mount(Component $component, $keyOfComponent): void
    {
        $this->component = $component;
        $this->keyOfComponent = $keyOfComponent;

        $this->currentFieldsFilter = array_key_first($this->orderedFields());
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ComponentType::class, null, ['component' => $this->component]);
    }

    #[ExposeInTemplate('metadata')]
    public function getMetadata(): EditorComponent
    {
        return $this->editorService->getComponentMetadata($this->component->getType());
    }

    #[ExposeInTemplate('fields')]
    public function orderedFields(): array
    {
        return $this->editorService->orderFieldsByCategory($this->editorService->getFieldsForComponent($this->component));
    }

    #[LiveAction]
    public function sync(Request $request): void
    {
        $this->submitForm();
        $form = $this->getForm();

        $this->emit('editor:update', [
            'keys' => $this->keyOfComponent,
            'data' => array_map(
                fn($data) => $this->dataHydrationExtension->dehydrate($data),
                $form->get('data')->getData()
            )
        ]);
    }

    #[LiveAction]
    public function filter(#[LiveArg] $name): void
    {
        $this->currentFieldsFilter = $name;
    }
}
