<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Type;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class IconType extends AbstractType
{
    private const AUTOCOMPLETE = 'Symfony\\UX\\Autocomplete\\AutocompleteBundle';

    /**
     * @param array<string, string> $icons
     */
    public function __construct(
        #[Autowire('%page_builder.icons%')]
        private array $icons,
    ) {
    }

    public function getParent(): string
    {
        return [] === $this->icons ? TextType::class : ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        if ([] === $this->icons) {
            return;
        }

        $defaults = [
            'choices' => $this->icons,
            'choice_translation_domain' => false,
        ];
        if (self::autocompleteAvailable()) {
            $defaults['autocomplete'] = true;
            $defaults['options_as_html'] = true;
        }
        $resolver->setDefaults($defaults);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['icon_autocomplete'] = [] !== $this->icons && self::autocompleteAvailable();
    }

    public function getBlockPrefix(): string
    {
        return 'builder_icon';
    }

    private static function autocompleteAvailable(): bool
    {
        return class_exists(self::AUTOCOMPLETE);
    }
}
