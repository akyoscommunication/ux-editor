<?php

namespace Akyos\UXEditor\Twig\Components;

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

#[AsLiveComponent('UX:Editor:ComponentEdit', template: '@UXEditor/components/ComponentEdit.html.twig')]
final class ComponentEdit extends AbstractController
{
    use DefaultActionTrait, ComponentWithFormTrait, ComponentToolsTrait, LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'c', updateFromParent: true)]
    public Component $component;

    #[LiveProp(writable: true, updateFromParent: true)]
    public string $keyOfComponent;

    #[LiveProp(writable: true)]
    public $currentFieldsFilter;

    public function __construct(
        private EditorService $editorService,
        private DataHydrationExtension $dataHydrationExtension
    ){}

    public function mount(Component $component, string $keyOfComponent): void
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
    public function sync(#[LiveArg] int $key, Request $request): void
    {
        // J'ai passé la key en paramètre car si je fais $this->keyOfComponent, et que en amont j'ai changer l'ordre avec sortable, j'ai toujours l'ancienne clé alors que sur le template, la clé est la bonne
        $this->submitForm();
        $form = $this->getForm();

        $this->emit('editor:update', [
            'keys' => $key,
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
