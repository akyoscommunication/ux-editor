<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Akyos\UXEditor\Hydration\ComponentHydrationExtension;
use Akyos\UXEditor\Hydration\ContentHydrationExtension;
use Akyos\UXEditor\Hydration\DataHydrationExtension;
use Akyos\UXEditor\Service\EditorService;
use Akyos\UXEditor\Twig\UXEditorExtension;
use Akyos\UXEditor\Twig\UXEditorExtensionRuntime;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_editor.twig_editor_extension', UXEditorExtension::class)
        ->tag('twig.extension')

        ->set('.ux_editor.twig_editor_runtime', UXEditorExtensionRuntime::class)
        ->args([
            service('.ux_editor.editor_component_hydration'),
            service('.ux_editor.editor_service'),
        ])
        ->tag('twig.runtime')

        ->set('.ux_editor.editor_content_hydration', ContentHydrationExtension::class)
        ->args([
            service('.ux_editor.editor_component_hydration'),
        ])
        ->tag('live_component.hydration_extension')

        ->set('.ux_editor.editor_component_hydration', ComponentHydrationExtension::class)
        ->args([
            service('.ux_editor.editor_data_hydration'),
            service('.ux_editor.editor_service'),
        ])
        ->tag('live_component.hydration_extension')

        ->set('.ux_editor.editor_data_hydration', DataHydrationExtension::class)
        ->tag('live_component.hydration_extension')

        ->set('.ux_editor.editor_service', EditorService::class)
        ->args([
            service('kernel'),
            service('.ux_editor.editor_data_hydration'),
            service('doctrine.orm.entity_manager'),
        ])
    ;
};
