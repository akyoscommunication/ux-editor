<?php

namespace Akyos\UXEditor\DependencyInjection\Compiler;

use Akyos\UXEditor\Attributes\EditorComponent;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Akyos\UXEditor\Service\EditorService;
use Symfony\UX\TwigComponent\DependencyInjection\Compiler\TwigComponentPass;

class UXEditorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $componentClassMap = [];
        $categoryDefinitions = [];
        // get ux_editor.categories configuration
        $config = $container->getExtensionConfig('ux_editor')[0];

        foreach ($container->findTaggedServiceIds('ux_editor.component') as $id => $tags) {
            $definition = $container->findDefinition($id);
            $fqcn = $definition->getClass();
            $componentClassMap[$fqcn] = $id;
        }

        $editorServiceDefinition = $container->findDefinition(EditorService::class);
        $editorServiceDefinition->setArgument(0, $componentClassMap);
        $editorServiceDefinition->setArgument(1, $config['categories']);
    }
}
