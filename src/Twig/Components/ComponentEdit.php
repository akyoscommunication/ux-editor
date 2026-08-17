<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Form\Type\ComponentType;
use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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
    ) {
    }

    public function mount(Component $component, string $keyOfComponent, string $editorId = ''): void
    {
        $this->component = $component;
        $this->keyOfComponent = $keyOfComponent;
        $this->editorId = $editorId;
        // ponytail: un nom de formulaire par bloc, sinon les id HTML (ex. media) se
        // dupliquent et ux-filemanager synchronise tous les widgets ciblés.
        $this->formName = $this->buildFormName($keyOfComponent);

        $this->currentFieldsFilter = array_key_first($this->orderedFields());
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->container->get('form.factory')->createNamed(
            $this->buildFormName($this->keyOfComponent),
            ComponentType::class,
            null,
            ['component' => $this->component],
        );
    }

    private function buildFormName(string $keyOfComponent): string
    {
        return 'component_' . str_replace('.', '_', $keyOfComponent);
    }

    /**
     * Pas de data-model : le flux editor-edit (debounced LiveAction sync) est
     * le seul à lire le DOM et persister les champs. Évite le double appel
     * live (norender) + sync sur chaque frappe dans les formulaires de blocs.
     */
    private function getDataModelValue(): ?string
    {
        return null;
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
    public function sync(#[LiveArg] string $key, #[LiveArg] array $formValues = []): void
    {
        if ([] !== $formValues) {
            $this->formValues = $formValues;
        }

        if (null !== $this->form || null !== $this->formView) {
            $this->resetForm(true);
        }

        $form = $this->getForm();
        $form->submit($this->formValues, false);
        // ponytail: sans ça, PreReRender::submitFormOnRender() re-soumet la même
        // instance dans la même requête → "A form can only be submitted once."
        $this->shouldAutoSubmitForm = false;

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
