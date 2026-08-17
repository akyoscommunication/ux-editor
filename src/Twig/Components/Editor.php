<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Hydration\ComponentHydrationExtension;
use Akyos\UXEditor\Hydration\ContentHydrationExtension;
use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Model\Content;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('UX:Editor', template: '@UXEditor/components/Editor.html.twig')]
final class Editor extends AbstractController
{
    use DefaultActionTrait, ComponentToolsTrait;

    public function __construct(
        private EditorService $editorService,
        private ContentHydrationExtension $contentHydrationExtension,
        private ComponentHydrationExtension $componentHydrationExtension,
    ){}

    #[LiveProp(writable: true)]
    public Content $value;

    #[LiveProp]
    public string $inputId;

    #[LiveProp]
    public array $allowedComponents = [];

    #[LiveProp]
    public bool $previewEnabled = true;

    #[ExposeInTemplate('components')]
    public function getComponents(): array
    {
        $return = [];
        $components = $this->editorService->getComponents($this->allowedComponents);
        // sort by category and put in array
        foreach ($components as $component) {
            foreach ($component['metadata']->categories as $category) {
                $return[$category->name][] = $component;
            }
        }

        return $return;
    }

    #[LiveAction]
    public function add(#[LiveArg] string $component, #[LiveArg] ?int $order = null, #[LiveArg] ?string $keys = null): void
    {
        $newComponent = (new Component())
            ->setType($component)
        ;
        if ($keys !== null) {
            $segments = explode('.', $keys);
            $current = $this->value;
            foreach ($segments as $segment) {
                $idx = (int) $segment;
                if ($current instanceof Content) {
                    $items = $current->getComponents();
                    $current = $items[$idx] ?? null;
                } else {
                    $items = $current->getChildren() ?? [];
                    $current = $items[$idx] ?? null;
                }
                if ($current === null) {
                    return;
                }
            }
            if (!$current instanceof Component) {
                return;
            }
            $order = \count($current->getChildren() ?? []);
            $current->addChild(
                $newComponent->setOrder($order)
            );

            return;
        }

        $order = $order ?? count($this->value->getComponents());

        // inject the new component into the array with the correct key
        $components = $this->value->getComponents();

        $components = array_merge(
            array_slice($components, 0, $order),
            [$newComponent->setOrder($order)],
            array_slice($components, $order)
        );

        // reindex the array
        $components = array_values($components);

        $this->value->setComponents($components);

        $this->saveToInput();
    }

    #[LiveListener('editor:remove')]
    public function remove(#[LiveArg] string $keys): void
    {
        $segments = explode('.', $keys);
        $removeIndex = (int) array_pop($segments);

        if ($segments === []) {
            $components = $this->value->getComponents();
            if (!isset($components[$removeIndex])) {
                return;
            }
            unset($components[$removeIndex]);
            $this->value->setComponents(array_values($components));
            $this->saveToInput();

            return;
        }

        $parent = $this->getNodeAtPath(implode('.', $segments));
        if (!$parent instanceof Component) {
            return;
        }
        $children = $parent->getChildren() ?? [];
        if (!isset($children[$removeIndex])) {
            return;
        }
        unset($children[$removeIndex]);
        $parent->setChildren(array_values($children));
        $this->saveToInput();
    }

    #[LiveAction]
    public function move(#[LiveArg] int $old, #[LiveArg] int $new, #[LiveArg] string $keys = ''): void
    {
        $keys = trim($keys);
        if ($keys === '') {
            $components = $this->value->getComponents();
        } else {
            $parent = $this->getNodeAtPath($keys);
            if (!$parent instanceof Component) {
                return;
            }
            $components = $parent->getChildren() ?? [];
        }

        if (!isset($components[$old])) {
            return;
        }

        $component = $components[$old];
        unset($components[$old]);
        $components = array_values($components);
        array_splice($components, $new, 0, [$component]);

        if ($keys === '') {
            $this->value->setComponents($components);
        } else {
            $parent = $this->getNodeAtPath($keys);
            if ($parent instanceof Component) {
                $parent->setChildren($components);
            }
        }

        $this->saveToInput();
    }

    #[LiveListener('editor:update')]
    public function update(#[LiveArg] string $keys, #[LiveArg] array $data, #[LiveArg] string $editorId = ''): void
    {
        // editor:update est diffuse a TOUS les UX:Editor de la page (emit global).
        // On n'applique que si l'event vise cet editeur, sinon un editeur voisin
        // tenterait de resoudre un chemin inexistant (500) ou ecraserait son contenu.
        if ($editorId === '' || $editorId !== $this->inputId) {
            return;
        }

        try {
            $current = $this->getCurrentComponent($keys);
        } catch (\InvalidArgumentException) {
            return;
        }

        $current->setData($data);

        $this->saveToInput();
    }

    private function getNodeAtPath(string $keys): Content|Component
    {
        if ($keys === '') {
            return $this->value;
        }

        $segments = explode('.', $keys);
        $current = $this->value;
        foreach ($segments as $segment) {
            $idx = (int) $segment;
            if ($current instanceof Content) {
                $items = $current->getComponents();
                $current = $items[$idx] ?? null;
            } else {
                $items = $current->getChildren() ?? [];
                $current = $items[$idx] ?? null;
            }
            if ($current === null) {
                throw new \InvalidArgumentException(\sprintf('Chemin de composant invalide : "%s".', $keys));
            }
        }

        return $current;
    }

    private function getCurrentComponent(string $keys): Component
    {
        $node = $this->getNodeAtPath($keys);
        if (!$node instanceof Component) {
            throw new \InvalidArgumentException(\sprintf('Le chemin "%s" ne désigne pas un composant.', $keys));
        }

        return $node;
    }

    #[LiveAction]
    public function save(Request $request): void
    {
        $files = $request->files->get('component');

        if (!empty($files)) {
            foreach ($files as $key => $file) {
                $current = $this->getCurrentComponent($key);
                $keyOfFile = null;
                $value = $file['data'];
                if (is_array($value)) {
                    list($value, $keyOfFile) = $this->getUploadedFile($value, $keyOfFile);
                }

                // TODO: configure the path
                $path = $this->getParameter('kernel.project_dir') . '/public/uploads';
                $movedFile = $value->move($path, $value->getClientOriginalName());
                $fullPath = $movedFile->getPathname();

                $pointerKeys = explode('.', $keyOfFile);
                $dataToSet = $current->getData();
                $pointer = &$dataToSet;
                foreach ($pointerKeys as $pointerKey) {
                    $pointer = &$pointer[$pointerKey];
                }

                $pointer = $fullPath;

                $current->setData($dataToSet);
            }
        }

        $this->saveToInput();
    }

    private function saveToInput(): void
    {
        $this->dispatchBrowserEvent('editor:save', [
            'content' => $this->contentHydrationExtension->dehydrate($this->value),
            'id' => $this->inputId,
        ]);
    }

    private function getUploadedFile($data, $key): array
    {
        if (is_array($data)) {
            // get next level
            $nextKey = key($data);
            $keyForNext = $key ? "$key.$nextKey" : $nextKey;
            list($data, $key) = $this->getUploadedFile($data[$nextKey], $keyForNext);
        }

        return [
            $data,
            $key,
        ];
    }
}
