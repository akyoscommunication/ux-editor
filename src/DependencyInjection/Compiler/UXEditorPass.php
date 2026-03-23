<?php

namespace Akyos\UXEditor\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Akyos\UXEditor\Service\EditorService;

class UXEditorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $componentClassMap = [];
        $categoryDefinitions = $container->hasParameter('ux_editor.categories')
            ? $container->getParameter('ux_editor.categories')
            : [];

        foreach ($container->findTaggedServiceIds('ux_editor.component') as $id => $tags) {
            $definition = $container->findDefinition($id);
            $fqcn = $definition->getClass();
            $componentClassMap[$fqcn] = $id;
        }

        $editorServiceDefinition = $container->findDefinition(EditorService::class);
        $editorServiceDefinition->setArgument(0, $componentClassMap);
        $editorServiceDefinition->setArgument(1, $categoryDefinitions);
    }
}
