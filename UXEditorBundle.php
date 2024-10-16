<?php

namespace Akyos\UXEditor;

use Akyos\UXEditor\DependencyInjection\Compiler\UXEditorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class UXEditorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Enregistrer le WorkflowCompilerPass
        $container->addCompilerPass(new UXEditorCompilerPass());
    }
}
