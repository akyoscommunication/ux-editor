<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Form\Type\ComponentType;
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
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('UX:Editor:ComponentEdit', template: '@UXEditor/components/ComponentEdit.html.twig')]
final class ComponentEdit extends AbstractController
{
    use DefaultActionTrait, ComponentToolsTrait, LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'c', updateFromParent: true)]
    public Component $component;

    #[LiveProp(writable: true, updateFromParent: true)]
    public string $keyOfComponent;

    #[LiveProp(updateFromParent: true)]
    public string $editorId = '';

    #[LiveProp(writable: true)]
    public $currentFieldsFilter;

    public function __construct(
        private EditorService $editorService,
    ){}

    public function mount(Component $component, string $keyOfComponent, string $editorId = ''): void
    {
        $this->component = $component;
        $this->keyOfComponent = $keyOfComponent;
        $this->editorId = $editorId;

        $this->currentFieldsFilter = array_key_first($this->orderedFields());
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ComponentType::class, null, ['component' => $this->component]);
    }

    /**
     * Map every field as a model (name => model) but suppress the automatic
     * re-render on each change. The "editor-edit" Stimulus controller owns the
     * sync flow (debounced LiveAction "sync"), so letting the default
     * "on(change)|*" trigger a server re-render would reset the interactive
     * builder widgets on every keystroke. See Symfony UX Live Component docs.
     */
    private function getDataModelValue(): ?string
    {
        return 'norender|on(input)|*';
    }

    #[ExposeInTemplate('metadata')]
    public function getMetadata(): ?EditorComponent
    {
        return $this->editorService->getComponentMetadata($this->component->getType());
    }

    #[ExposeInTemplate('fields')]
    public function orderedFields(): array
    {
        return $this->editorService->orderFieldsByCategory($this->editorService->getFieldsForComponent($this->component));
    }

    #[LiveAction]
    public function sync(#[LiveArg] string $key, Request $request): void
    {
        $this->submitForm();
        $form = $this->getForm();

        // ponytail: emit() global (et non emitUp) car emitUp resout le parent via
        // le DOM (element.contains) et devient instable pendant les re-render morphdom,
        // ce qui faisait perdre des sync. emit() est fiable; on cible le bon editeur
        // cote serveur via editorId. Plafond : chaque sync re-render tous les UX:Editor
        // de la page (un par dialog). Upgrade : event d'ecoute unique par instance.
        $this->emit('editor:update', [
            'keys' => $key,
            'data' => $form->get('data')->getData(),
            'editorId' => $this->editorId,
        ]);
    }

    #[LiveAction]
    public function filter(#[LiveArg] $name): void
    {
        $this->currentFieldsFilter = $name;
    }
}
