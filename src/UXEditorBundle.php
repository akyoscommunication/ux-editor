<?php

namespace Akyos\UXEditor;

use Akyos\UXEditor\DependencyInjection\Compiler\UXEditorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class UXEditorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new UXEditorPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
