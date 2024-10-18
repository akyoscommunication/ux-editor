<?php

namespace Akyos\UXEditor\Service;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Attributes\EditorField;
use Akyos\UXEditor\Form\Type\ComponentType;
use Akyos\UXEditor\Form\Type\DataType;
use Akyos\UXEditor\Form\Type\Editor\EditorEntityType;
use Akyos\UXEditor\Form\Type\Editor\EditorFileType;
use Akyos\UXEditor\Hydration\DataHydrationExtension;
use Akyos\UXEditor\Model\Category;
use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Model\Data;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class EditorService
{
    public function __construct(
        private KernelInterface $kernel,
        private DataHydrationExtension $dataHydrationExtension,
        private EntityManagerInterface $em,
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

    public function getFieldsDefaultDataForComponent(Component $component, array $dataToSet = []): array
    {
        $fields = $this->getFieldsForComponent($component);
        $data = [];
        foreach ($fields as $key => $value) {
            $d = new Data($key, $value['property']->getDefaultValue());
            $data[$key] = $d;

            // if the key exists in the data array, use that value
            if (array_key_exists($key, $dataToSet) && $dataToSet[$key]) {
                $data[$key] = $d->setValue($dataToSet[$key]->getValue());
            }
        }

        return $data;
    }

    public function getComponentMetadata(?string $getType)
    {
        $component = $this->findComponentByTwigName($getType);
        if ($component) {
            return $component['metadata'];
        }

        return null;
    }

    public function getDataFieldMetadata(Component $component, Data $data): array
    {
        $fields = $this->getFieldsForComponent($component);
        $value = $fields[$data->getName()];
        $realValue = $data->getValue();

        $type = $value['metadata']->type;
        $typeOpts = [
            'label' => $value['metadata']->label ?: $data->getName(),
        ];
        $propertyType = $value['property']->getType()->getName();

        // TODO reprendre le truc de base du bundle form sy ?
        switch ($propertyType) {
            case 'string':
                $type = $type ?: TextType::class;
                break;
            case 'array':
                $type = $type ?: CollectionType::class;
                $subComponent = $value['metadata']->component;
                if ($subComponent) {
                    $reflection = new \ReflectionClass($subComponent);

                    if ($editorComponent = $reflection->getAttributes(EditorComponent::class)) {
                        // if $propertyType class exists and is an component with EditorComponent attribute, use CollectionType
                        // and set defaut data with the component default data

                        $editorComponentInstance = $editorComponent[0]->newInstance();
                        $componentInstance = (new Component())->setType($this->getTwigName($subComponent));
                        $datas = isset($component->getData()[$value['property']->getName()]) && $component->getData()[$value['property']->getName()] instanceof Data ? $component->getData()[$value['property']->getName()]->getValue() : [];
                        foreach ($datas as $key => $d) {
                            $datas[$key] = $this->dataHydrationExtension->hydrate([
                                'name' => $key,
                                'value' => $d,
                            ]);
                        }

                        $typeOpts = [
                            'label' => $editorComponentInstance->label ?: $data->getName(),
                            'entry_type' => DataType::class,
                            'entry_options' => [
                                'label' => false,
                                'component' => $componentInstance,
                            ],
                            'data' => $this->getFieldsDefaultDataForComponent($componentInstance, $datas),
                        ];
                    }
                }
                break;
            case 'int':
                $type = $type ?: IntegerType::class;
                break;
            case 'bool':
                $type = $type ?: CheckboxType::class;
                break;
            default:
                $type = $type ?: CollectionType::class;

                if (class_exists($propertyType)) {
                    try {
                        $isEntityClass = !$this->em->getMetadataFactory()->isTransient($propertyType);
                    } catch (MappingException $e) {
                        $isEntityClass = false;
                    }

                    // if $propertyType class exists and is an entity, use EntityType
                    if ($isEntityClass) {
                        $type = $type ?: EntityType::class;
                        $realValue = is_int($realValue) ? $this->em->getRepository($propertyType)->find($realValue) : $realValue;
                    }
                }
                break;
        }

        switch ($type) {
            case EntityType::class:
                $type = EditorEntityType::class;
                $typeOpts = array_merge($typeOpts, [
                    'class' => $value['property']->getType()->getName(),
                    'placeholder' => 'Choose an option',
                    'choice_value' => 'id',
                ]);
                break;
            case FileType::class:
                $type = EditorFileType::class;
                break;
            case LiveCollectionType::class:
                $componentClass = $value['metadata']->typeOpts['component'];
                unset($value['metadata']->typeOpts['component']);
                $component = (new Component())->setType($this->getTwigName($componentClass));
                $typeOpts = array_merge($typeOpts, [
                    'entry_type' => ComponentType::class,
                    'entry_options' => [
                        'label' => false,
                        'component' => $component,
                    ],
                ]);
                break;
        }

        return [
            'type' => $type,
            'typeOpts' => array_merge($typeOpts, $value['metadata']->typeOpts),
            'editorField' => $value['metadata'],
            'realValue' => $realValue,
        ];
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
