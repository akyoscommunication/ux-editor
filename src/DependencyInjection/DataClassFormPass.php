<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\DependencyInjection;

use Builder\PageBuilderBundle\Form\DataClassType;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\FormType;

/**
 * Registers #[AsFormType] classes as form types on Symfony 8.1.
 * ponytail: remove when symfony/form provides DataClassType (8.2).
 */
final class DataClassFormPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('form.extension') || class_exists(\Symfony\Component\Form\DataClassType::class)) {
            return;
        }

        $extension = $container->getDefinition('form.extension');
        $locatorId = (string) $extension->getArgument(0);
        if (!$container->hasDefinition($locatorId)) {
            return;
        }

        $locator = $container->getDefinition($locatorId);
        $services = $locator->getArgument(0);
        if (!\is_array($services)) {
            return;
        }

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if (!\is_string($class) || isset($services[$class]) || !class_exists($class)) {
                continue;
            }
            $reflection = $container->getReflectionClass($class);
            if (!$reflection || !$reflection->getAttributes(AsFormType::class)) {
                continue;
            }

            $typeId = '.form.data_class_type.'.$class;
            $container->setDefinition($typeId, $this->typeDefinition($reflection));
            $services[$class] = new ServiceClosureArgument(new Reference($typeId));
        }

        $locator->setArgument(0, $services);
    }

    private function typeDefinition(\ReflectionClass $reflection): Definition
    {
        $asForm = $reflection->getAttributes(AsFormType::class)[0]->newInstance();
        $fields = [];
        foreach ($reflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $fieldAttributes = $property->getAttributes(FormField::class);
            if (!$fieldAttributes) {
                continue;
            }
            $field = $fieldAttributes[0]->newInstance();
            $fields[$property->getName()] = [$field->type, $field->options];
        }

        $parent = FormType::class;
        $parentReflection = $reflection;
        while ($parentReflection = $parentReflection->getParentClass()) {
            if ($parentReflection->getAttributes(AsFormType::class)) {
                $parent = $parentReflection->getName();
                break;
            }
        }

        $short = substr($reflection->getName(), strrpos($reflection->getName(), '\\') + 1);
        $prefix = strtolower((string) preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $short));

        return new Definition(DataClassType::class, [
            $reflection->getName(),
            $parent,
            $prefix,
            $fields,
            $asForm->options,
        ]);
    }
}
