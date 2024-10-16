<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Akyos\UXEditor\Attributes\EditorComponent;
use Akyos\UXEditor\Hydration\ComponentHydrationExtension;
use Akyos\UXEditor\Hydration\ContentHydrationExtension;
use Akyos\UXEditor\Hydration\DataHydrationExtension;
use Akyos\UXEditor\Service\EditorService;
use Akyos\UXEditor\Twig\Components\ComponentEdit;
use Akyos\UXEditor\Twig\Components\ComponentLayer;
use Akyos\UXEditor\Twig\Components\Editor;
use Akyos\UXEditor\Twig\Components\Render;
use Akyos\UXEditor\Twig\UXEditorExtension;
use Akyos\UXEditor\Twig\UXEditorExtensionRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_editor.twig_component.editor', Editor::class)
        ->args([
            service('.ux_editor.editor_service'),
            service('.ux_editor.editor_content_hydration'),
            service('.ux_editor.editor_component_hydration'),
            service('.ux_editor.editor_data_hydration'),
        ])
        ->tag('twig.component', [
            'key' => 'UX:Editor',
            'template' => '@UXEditor/_components/editor.html.twig',
            'expose_public_props' => true,
            'attributes_var' => 'attributes',
            'live' => true,
            'csrf' => true,
            'route' => 'ux_live_component',
            'method' => 'post',
            'url_reference_type' => true,
        ])
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber')

        ->set('.ux_editor.twig_component.component_edit', ComponentEdit::class)
        ->args([
            service('.ux_editor.editor_service'),
            service('.ux_editor.editor_data_hydration'),
        ])
        ->tag('twig.component', [
            'key' => 'UX:Editor:ComponentEdit',
            'template' => '@UXEditor/_components/component_edit.html.twig',
            'expose_public_props' => true,
            'attributes_var' => 'attributes',
            'live' => true,
            'csrf' => true,
            'route' => 'ux_live_component',
            'method' => 'post',
            'url_reference_type' => true,
        ])
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber')

        ->set('.ux_editor.twig_component.component_edit', ComponentLayer::class)
        ->args([
            service('.ux_editor.editor_service'),
            service('.ux_editor.editor_data_hydration'),
        ])
        ->tag('twig.component', [
            'key' => 'UX:Editor:ComponentLayer',
            'template' => '@UXEditor/_components/component_layer.html.twig',
            'expose_public_props' => true,
            'attributes_var' => 'attributes',
            'live' => true,
            'csrf' => true,
            'route' => 'ux_live_component',
            'method' => 'post',
            'url_reference_type' => true,
        ])
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber')

        ->set('.ux_editor.twig_component.component_edit', Render::class)
        ->tag('twig.component', [
            'key' => 'UX:Editor:Render',
            'template' => '@UXEditor/_components/render.html.twig',
        ])
    ;
};
