<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\DependencyInjection;

use Builder\PageBuilderBundle\Service\BlockRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

final class BlockRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(BlockRegistry::class)) {
            return;
        }

        $definitions = [];
        $defaultBlocks = !$container->hasParameter('page_builder.default_blocks') || $container->getParameter('page_builder.default_blocks');
        // findTaggedServiceIds skips container.excluded. #[AsFormType] adds that tag,
        // which would also hide the Twig/Live component from TwigComponentPass.
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition->hasTag('builder.block') || str_ends_with($id, '.builder_component')) {
                continue;
            }
            $tag = $definition->getTag('builder.block')[0];
            $class = $definition->getClass() ?? $id;
            // Sans composant cloné ni entrée au registre, le bloc n'existe ni dans la bibliothèque ni au rendu.
            if (!$defaultBlocks && str_starts_with($class, 'Builder\\PageBuilderBundle\\Block\\')) {
                continue;
            }
            $reflection = $container->getReflectionClass($class);
            $componentAttributes = $reflection->getAttributes(AsTwigComponent::class, \ReflectionAttribute::IS_INSTANCEOF);
            $component = '';
            $template = '';
            $live = false;
            if ($componentAttributes) {
                $attribute = $componentAttributes[0]->newInstance();
                $config = $attribute->serviceConfig();
                $component = (string) ($config['key'] ?? $tag['name']);
                $template = (string) ($config['template'] ?? '');
                $live = $attribute instanceof AsLiveComponent;
                $componentDefinition = clone $definition;
                $componentDefinition->clearTag('container.excluded');
                $componentDefinition->clearTag('form.data_class');
                $componentDefinition->setShared(false);
                $container->setDefinition($id.'.builder_component', $componentDefinition);
            }

            $containerFlag = ($tag['container'] ?? '0') === '1' || ($tag['container'] ?? false) === true;
            if (!$containerFlag && $template === '') {
                throw new \LogicException(sprintf('Le bloc "%s" doit déclarer un template de composant Twig ou Live.', $tag['name']));
            }

            $definitions[$tag['name']] = [
                'name' => $tag['name'],
                'class' => $class,
                'label' => $tag['label'],
                'category' => $tag['category'],
                'icon' => $tag['icon'],
                'container' => $containerFlag,
                'component' => $component,
                'template' => $template,
                'live' => $live,
            ];
        }

        $container->getDefinition(BlockRegistry::class)->setArgument('$definitions', $definitions);
    }
}
