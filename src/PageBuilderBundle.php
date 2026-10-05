<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Builder\PageBuilderBundle\DependencyInjection\BlockRegistryPass;
use Builder\PageBuilderBundle\DependencyInjection\DataClassFormPass;
use Builder\PageBuilderBundle\Form\Type\BuilderImageType;
use Builder\PageBuilderBundle\Integration\JoliAdminAssetsSubscriber;
use Builder\PageBuilderBundle\Integration\JoliMediaStorage;
use Builder\PageBuilderBundle\Integration\PrettyMediaAdminRouter;
use Builder\PageBuilderBundle\Model\Margin;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class PageBuilderBundle extends AbstractBundle
{
    private const CANVAS_FONT = 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('joli_media')->defaultFalse()->end()
                ->arrayNode('canvas')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('stylesheet')->defaultValue('%kernel.project_dir%/assets/styles/app.css')->end()
                        ->scalarNode('class')->defaultValue('')->end()
                        ->arrayNode('fonts')
                            ->scalarPrototype()->end()
                            ->defaultValue([self::CANVAS_FONT])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('margin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('top')->min(0)->max(128)->defaultValue(0)->end()
                        ->integerNode('right')->min(0)->max(128)->defaultValue(0)->end()
                        ->integerNode('bottom')->min(0)->max(128)->defaultValue(0)->end()
                        ->integerNode('left')->min(0)->max(128)->defaultValue(0)->end()
                    ->end()
                ->end()
                ->arrayNode('icons')
                    ->info('Libellé => nom UX Icons. Vide : IconType est un champ texte.')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();
    }

    public function build(ContainerBuilder $container): void
    {
        // After attribute tags exist (priority 100), before FormPass and TwigComponentPass (priority 0).
        $container->addCompilerPass(new BlockRegistryPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        $container->addCompilerPass(new DataClassFormPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);

        $container->registerAttributeForAutoconfiguration(
            AsBuilderBlock::class,
            static function (ChildDefinition $definition, AsBuilderBlock $attribute): void {
                $definition->addTag('builder.block', [
                    'name' => $attribute->name,
                    'label' => $attribute->label,
                    'category' => $attribute->category,
                    'icon' => $attribute->icon,
                    'container' => $attribute->container ? '1' : '0',
                ]);
            },
        );
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($config['joli_media'] && !self::classAvailable(BuilderImageType::JOLI_LIBRARY)) {
            throw new \LogicException('page_builder.joli_media est activé, mais '.BuilderImageType::JOLI_LIBRARY.' est introuvable. Installez jolicode/media-bundle.');
        }

        $joliAdmin = $config['joli_media'] && self::joliAdminAvailable();

        $container->import('../config/services.yaml');
        if ($config['joli_media']) {
            $container->services()
                ->set(JoliMediaStorage::class)
                ->arg('$libraries', service(BuilderImageType::JOLI_LIBRARY));
        }
        if ($joliAdmin) {
            $container->services()
                ->set(PrettyMediaAdminRouter::class)
                    ->decorate('joli_media_easy_admin.router')
                    ->autowire()
                ->set(JoliAdminAssetsSubscriber::class)
                    ->tag('kernel.event_subscriber');
        }
        $container->parameters()->set('page_builder.joli_media', $config['joli_media']);
        $container->parameters()->set('page_builder.joli_admin', $joliAdmin);
        $container->parameters()->set('page_builder.margin', Margin::normalize($config['margin']));
        $container->parameters()->set('page_builder.canvas_stylesheet', $config['canvas']['stylesheet']);
        $container->parameters()->set('page_builder.icons', self::normalizeIcons($config['icons']));
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $joliMedia = false;
        $canvasClass = '';
        $canvasFonts = [self::CANVAS_FONT];
        foreach ($builder->getExtensionConfig('page_builder') as $config) {
            if (\array_key_exists('joli_media', $config)) {
                $joliMedia = (bool) $config['joli_media'];
            }
            if (isset($config['canvas']['class'])) {
                $canvasClass = (string) $config['canvas']['class'];
            }
            if (isset($config['canvas']['fonts'])) {
                $canvasFonts = array_values($config['canvas']['fonts']);
            }
        }

        $container->extension('twig', [
            'globals' => [
                'builder_joli_media' => $joliMedia,
                'builder_joli_admin' => $joliMedia && self::joliAdminAvailable(),
                'builder_canvas_class' => $canvasClass,
                'builder_canvas_fonts' => $canvasFonts,
            ],
        ]);

        $container->extension('framework', [
            'asset_mapper' => [
                'paths' => [
                    $this->getPath().'/assets' => 'page-builder',
                ],
            ],
        ]);

        $container->extension('stimulus', [
            'controller_paths' => [
                '%kernel.project_dir%/assets/controllers',
                $this->getPath().'/assets/controllers',
            ],
        ]);

        $container->extension('symfonycasts_tailwind', [
            'input_css' => [
                $this->getPath().'/assets/builder.css',
                $this->getPath().'/assets/canvas.css',
            ],
        ]);
    }

    /**
     * @param array<int|string, mixed> $icons
     *
     * @return array<string, string>
     */
    private static function normalizeIcons(array $icons): array
    {
        $choices = [];
        foreach ($icons as $label => $name) {
            if (!\is_scalar($name) || '' === (string) $name) {
                continue;
            }
            $name = (string) $name;
            $choices[\is_int($label) ? $name : (string) $label] = $name;
        }

        return $choices;
    }

    private static function joliAdminAvailable(): bool
    {
        // MediaChoiceType est livré avec media-bundle. Le sélecteur n’existe que si EasyAdmin est installé.
        return self::classAvailable(BuilderImageType::JOLI_ADMIN_BUNDLE)
            && self::classAvailable(BuilderImageType::JOLI_ADMIN_TYPE);
    }

    private static function classAvailable(string $class): bool
    {
        try {
            return class_exists($class);
        } catch (\Throwable) {
            return false;
        }
    }
}
