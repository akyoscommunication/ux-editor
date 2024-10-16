<?php

namespace Akyos\UXEditor\Service;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Attributes\EditorField;
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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpKernel\KernelInterface;

class EditorService
{
    public function __construct(
        private KernelInterface $kernel,
        private DataHydrationExtension $dataHydrationExtension,
        private EntityManagerInterface $em,
    ){}

    public function getTwigName($class): string
    {
        return str_replace(['Akyos\UXEditor\\Twig\\Components\\', '\\'], ['', ':'], $class);
    }

    public function getComponents(?array $allowedComponents = []): array
    {
        // find all components with EditorComponent attribute
        $components = [];
        $finder = new Finder();
        /** @TODO: make updatable */
        $dir = $this->kernel->getProjectDir() . '/src/Twig/Components';
        $finder->files()->in($dir)->name('*.php');
        foreach ($finder as $file) {
            $class = 'App/Twig/Components/' . $file->getRelativePathname();
            $class = str_replace('/', '\\', $class);
            $class = str_replace('.php', '', $class);
            $reflection = new \ReflectionClass($class);
            if ($editorComponent = $reflection->getAttributes(EditorComponent::class)) {
                $twigName = $this->getTwigName($class);
                if (!empty($allowedComponents)) {
                    // @TODO: revoir ça parce que c'est chiant de tout le temps mapper pour avoir le twigname
                    $allowedComponents = array_map(fn($c) => $this->getTwigName($c), $allowedComponents);
                    if (!in_array($twigName, $allowedComponents)) continue;
                }

                $component = [
                    'class' => $class,
                    'metadata' => $editorComponent[0]->newInstance(),
                    'twigName' => $twigName,
                ];

                // check for EditorField attributes
                $fields = [];
                foreach ($reflection->getProperties() as $property) {
                    $field = $property->getAttributes(EditorField::class);
                    if ($field) {
                        $fields[$property->getName()] = [
                            'metadata' => $field[0]->newInstance(),
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
                ], $value['metadata']->typeOpts);
                break;
            case FileType::class:
                $type = EditorFileType::class;
                break;
        }

        return [
            'type' => $type,
            'typeOpts' => $typeOpts,
            'editorField' => $value['metadata'],
            'realValue' => $realValue,
        ];
    }
}
