<?php

namespace Akyos\UXEditor\Service;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Attributes\EditorField;
use Akyos\UXEditor\Model\Category;
use Akyos\UXEditor\Model\Component;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

readonly class EditorService
{
    public function __construct(
        private array $componentClassMap = [],
        private array $categoryDefinitions = [],
    ){}

    public function getTwigName($class): string
    {
        // TODO: revoir ça, essayer d'utiliser les fonctions de TwigComponentBundle
        return str_replace(['App\\Twig\\Components\\', '\\'], ['', ':'], $class);
    }

    public function getComponents(?array $allowedComponents = []): array
    {
        // find all components with EditorComponent attribute
        $components = [];
        foreach ($this->componentClassMap as $class) {
            $reflection = new \ReflectionClass($class);
            if ($editorComponent = $reflection->getAttributes(EditorComponent::class)) {
                $twigName = $this->getTwigName($class);
                if (!empty($allowedComponents)) {
                    // @TODO: revoir ça parce que c'est chiant de tout le temps mapper pour avoir le twigname
                    $allowedComponents = array_map(fn($c) => $this->getTwigName($c), $allowedComponents);
                    if (!in_array($twigName, $allowedComponents)) continue;
                }

                $editorComponentInstance = $editorComponent[0]->newInstance();
                $editorComponentInstance->categories = array_map(fn($c) => $this->setCategoryFromThing($c), $editorComponentInstance->categories);

                $component = [
                    'class' => $class,
                    'metadata' => $editorComponentInstance,
                    'twigName' => $twigName,
                ];

                // check for EditorField attributes
                $fields = [];
                foreach ($reflection->getProperties() as $property) {
                    if ($field = $property->getAttributes(EditorField::class)) {
                        $editorFieldInstance = $field[0]->newInstance();
                        $editorFieldInstance->category = $this->setCategoryFromThing($editorFieldInstance->category);

                        $fields[$property->getName()] = [
                            'metadata' => $editorFieldInstance,
                            'property' => $property,
                        ];
                    }
                }

                $component['fields'] = $fields;
                $components[] = $component;
            }
        }

        return $components;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function orderFieldsByCategory(array $fields): array
    {
        $categories = [];
        foreach ($fields as $key => $field) {
            $category = $field['metadata']->category;
            if ($category) {
                if (is_string($category)) {
                    if (isset($this->categoryDefinitions[$category]) && ($c = $this->categoryDefinitions[$category])) {
                        $category = new Category($c['label'], $c['icon']);
                    } else {
                        $category = new Category($category);
                    }
                }

                $c = [
                    'metadata' => $category,
                    'fields' => [],
                ];
                if (array_key_exists($category->name, $categories)) {
                    $c = $categories[$category->name];
                }
                $c['fields'][] = $field;
                $categories[$category->name] = $c;
            } else {
                $c = [
                    // TODO: définir une catégorie par défaut à utiliser
                    'metadata' => new Category('General', 'iconoir:general'),
                    'fields' => [],
                ];
                if (array_key_exists('General', $categories)) {
                    $c = $categories['General'];
                }
                $c['fields'][] = $field;
                $categories['General'] = $c;
            }
        }

        return $categories;
    }

    public function findComponentByTwigName(string $twigName): ?array
    {
        $components = $this->getComponents();
        foreach ($components as $component) {
            if ($component['twigName'] === $twigName) {
                return $component;
            }
        }

        return null;
    }

    public function getFieldsForComponent(Component $component): array
    {
        $component = $this->findComponentByTwigName($component->getType());
        if ($component) {
            return $component['fields'];
        }

        return [];
    }

    public function getComponentMetadata(?string $getType)
    {
        $component = $this->findComponentByTwigName($getType);
        if ($component) {
            return $component['metadata'];
        }

        return null;
    }

    public function setCategoryFromThing(mixed $thing): Category
    {
        if ($thing instanceof Category) {
            return $thing;
        }

        if (is_string($thing)) {
            return new Category($thing);
        }

        if (is_array($thing)) {
            return new Category($thing['label'], $thing['icon']);
        }

        return new Category('General', 'iconoir:general');
    }
}
