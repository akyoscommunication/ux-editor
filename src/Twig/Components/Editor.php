<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Hydration\ComponentHydrationExtension;
use Akyos\UXEditor\Hydration\ContentHydrationExtension;
use Akyos\UXEditor\Hydration\DataHydrationExtension;
use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Model\Content;
use Akyos\UXEditor\Model\Data;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private DataHydrationExtension $dataHydrationExtension
    ){}

    #[LiveProp(writable: true)]
    public Content $value;

    #[LiveProp]
    public string $inputId;

    #[LiveProp]
    public array $allowedComponents = [];

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
            $keys = explode('.', $keys);
            $current = $this->value;
            foreach ($keys as $key) {
                $current = $current[$key];
            }
            $order = count($current->getChildren());
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
        $explodedKeys = explode('.', $keys);
        $current = $this->getCurrentComponent($keys);

        if (count($explodedKeys) === 1) {
            $this->value->removeComponent($current);
        } else {
            unset($current);
        }

        $this->saveToInput();
    }

    #[LiveAction]
    public function move(#[LiveArg] int $old, #[LiveArg] int $new, #[LiveArg] string $keys): void
    {
        $current = $this->getCurrentComponent($keys);

        $components = $this->value->getComponents();
        $component = $components[$old];
        unset($components[$old]);
        array_splice($components, $new, 0, [$component]);

        $this->value->setComponents(array_values($components));

        $this->saveToInput();
    }

    #[LiveListener('editor:update')]
    public function update(#[LiveArg] string $keys, #[LiveArg] array $data): void
    {
        $current = $this->getCurrentComponent($keys);

        $dataToSet = [];
        foreach ($data as $k => $value) {
            $dataToSet[$k] = $this->dataHydrationExtension->hydrate(['name' => $k, 'value' => $value]);
        }

        $current->setData($dataToSet);

        $this->saveToInput();
    }

    private function getCurrentComponent(string $keys): Component
    {
        // keys is like : 0.1.1
        // we need to find the correct component and update it
        $keys = explode('.', $keys);
        $current = $this->value->getComponents();
        foreach ($keys as $key) {
            if ($current instanceof Component) {
                $current = $current->getChildren()[(int)$key];
            } else {
                $current = $current[$key];
            }
        }

        return $current;
    }

    #[LiveAction]
    public function save(Request $request): void
    {
        $files = $request->files->get('component');

        if (!empty($files)) {
            foreach ($files as $key => $file) {
                $current = $this->getCurrentComponent($key);

                $datas = $file['data'];

                foreach ($datas as $k => $data) {
                    /** @var UploadedFile $value */
                    $value = $data['value'];

                    // TODO: configure the path
                    $path = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $movedFile = $value->move($path, $value->getClientOriginalName());
                    $fullPath = $movedFile->getPathname();
                    if (isset($current->getData()[$k])) {
                        $current->getData()[$k]->setValue($fullPath);
                    } else {
                        $newData = (new Data())
                            ->setName($k)
                            ->setValue($fullPath)
                        ;
                        $current->addData($newData);
                    }
                }
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
}
