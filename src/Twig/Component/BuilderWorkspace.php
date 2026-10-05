<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Twig\Component;

use Builder\PageBuilderBundle\Form\Type\EmptyInspectorType;
use Builder\PageBuilderBundle\Model\BlockDefinition;
use Builder\PageBuilderBundle\Model\Document;
use Builder\PageBuilderBundle\Model\Margin;
use Builder\PageBuilderBundle\Service\BlockHydrator;
use Builder\PageBuilderBundle\Service\BlockRegistry;
use Builder\PageBuilderBundle\Service\BlockRenderer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent('BuilderWorkspace', template: '@PageBuilder/builder.html.twig')]
final class BuilderWorkspace
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public array $document = ['blocks' => []];

    #[LiveProp]
    public string $targetInput = '';

    #[LiveProp]
    public ?string $selectedId = null;

    #[LiveProp]
    public bool $inspectorFull = false;

    #[LiveProp]
    public ?array $clipboard = null;

    /** @var list<array<string, mixed>> */
    #[LiveProp]
    public array $history = [];

    public function __construct(
        private BlockRegistry $registry,
        private BlockHydrator $hydrator,
        private BlockRenderer $renderer,
        private FormFactoryInterface $formFactory,
        #[Autowire('%page_builder.margin%')]
        private array $defaultMargin,
    ) {
        $this->formName = 'inspector';
    }

    /** @return array<string, list<BlockDefinition>> */
    public function library(): array
    {
        return $this->registry->grouped();
    }

    public function tree(): array
    {
        return $this->renderer->decorate($this->document['blocks'] ?? []);
    }

    public function selectedDefinition(): ?BlockDefinition
    {
        $block = $this->selectedId ? Document::find($this->document, $this->selectedId) : null;

        return $block && $this->registry->has($block['type']) ? $this->registry->get($block['type']) : null;
    }

    #[LiveAction]
    public function toggleInspector(): void
    {
        $this->capture();
        $this->inspectorFull = !$this->inspectorFull;
    }

    #[LiveAction]
    public function select(#[LiveArg] string $id): void
    {
        $this->capture();
        $this->selectedId = $id;
        $this->resetForm();
    }

    #[LiveAction]
    public function insert(
        #[LiveArg] string $type,
        #[LiveArg] ?int $index = null,
        #[LiveArg] string $slot = '',
        #[LiveArg] string $parentId = '',
    ): void {
        $this->capture();
        $this->remember();
        $definition = $this->registry->get($type);
        $props = $this->hydrator->defaults($definition->class);
        $block = Document::create($type, $props, $definition->container, $this->defaultMargin);
        $this->document = Document::insert(
            $this->document,
            $block,
            '' !== $parentId ? $parentId : null,
            '' !== $slot ? (int) $slot : null,
            $index ?? \PHP_INT_MAX,
        );
        $this->selectedId = $block['id'];
        $this->resetForm();
    }

    #[LiveAction]
    public function move(
        #[LiveArg] string $id,
        #[LiveArg] int $index,
        #[LiveArg] string $slot = '',
        #[LiveArg] string $parentId = '',
    ): void {
        $this->capture();
        $this->remember();
        $this->document = Document::move(
            $this->document,
            $id,
            '' !== $parentId ? $parentId : null,
            '' !== $slot ? (int) $slot : null,
            $index,
        );
    }

    #[LiveAction]
    public function remove(#[LiveArg] string $id): void
    {
        $this->capture();
        $this->remember();
        $this->document = Document::remove($this->document, $id);
        if ($this->selectedId === $id) {
            $this->selectedId = null;
            $this->resetForm();
        }
    }

    #[LiveAction]
    public function duplicate(#[LiveArg] string $id): void
    {
        $this->capture();
        $this->remember();
        $this->document = Document::duplicate($this->document, $id);
    }

    #[LiveAction]
    public function copy(): void
    {
        $this->capture();
        if (null === $this->selectedId) {
            return;
        }
        $this->clipboard = Document::find($this->document, $this->selectedId);
    }

    #[LiveAction]
    public function paste(): void
    {
        if (null === $this->clipboard || null === $this->selectedId) {
            return;
        }
        $this->capture();
        $this->remember();
        $this->document = Document::paste($this->document, $this->clipboard, $this->selectedId);
    }

    #[LiveAction]
    public function undo(): void
    {
        if ([] === $this->history) {
            return;
        }
        $this->document = array_pop($this->history);
        if (null !== $this->selectedId && null === Document::find($this->document, $this->selectedId)) {
            $this->selectedId = null;
        }
        $this->resetForm();
    }

    #[PreReRender(priority: -10)]
    public function syncProps(): void
    {
        if (!$this->selectedId || !$this->getForm()->isSubmitted()) {
            return;
        }
        $data = $this->getForm()->getData();
        if (!\is_object($data)) {
            return;
        }
        $this->writeProps($data);
    }

    #[PreReRender(priority: -20)]
    public function publishDocument(): void
    {
        $this->dispatchBrowserEvent('builder:document', [
            'input' => $this->targetInput,
            'document' => $this->document,
        ]);
    }

    protected function instantiateForm(): FormInterface
    {
        $block = $this->selectedId ? Document::find($this->document, $this->selectedId) : null;
        if (null === $block || !$this->registry->has($block['type'])) {
            return $this->formFactory->createNamed('inspector', EmptyInspectorType::class);
        }

        $definition = $this->registry->get($block['type']);

        return $this->formFactory->createNamed('inspector', $definition->class, $this->hydrator->hydrate($definition->class, $block['props'] ?? []), [
            'builder_margin' => Margin::normalize($block['margin'] ?? null),
        ]);
    }

    private function remember(): void
    {
        $this->history = Document::remember($this->history, $this->document);
    }

    private function capture(): void
    {
        if (!$this->selectedId) {
            return;
        }
        $this->submitForm(false);
        $form = $this->getForm();
        if (!$form->isSynchronized()) {
            return;
        }
        $data = $form->getData();
        if (\is_object($data)) {
            $this->writeProps($data);
        }
    }

    private function writeProps(object $data): void
    {
        $id = (string) $this->selectedId;
        $props = $this->hydrator->extract($data);
        $this->document = Document::updateProps($this->document, $id, $props);
        $form = $this->getForm();
        if ($form->has('margin')) {
            $this->document = Document::updateMargin($this->document, $id, $form->get('margin')->getData() ?? []);
        }
        $this->document = Document::normalize(
            $this->document,
            fn (string $type): bool => $this->registry->has($type) && $this->registry->get($type)->container,
        );
    }
}
