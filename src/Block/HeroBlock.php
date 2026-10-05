<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Builder\PageBuilderBundle\Attribute\DependsOn;
use Builder\PageBuilderBundle\Form\Type\BuilderImageType;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsBuilderBlock(name: 'hero', label: 'Hero', category: 'Mise en page', icon: 'heroicons:sparkles')]
#[AsTwigComponent('builder:hero', template: '@PageBuilder/blocks/hero.html.twig')]
#[AsFormType]
class HeroBlock
{
    #[FormField(ChoiceType::class, [
        'label' => 'Disposition',
        'choices' => ['Simple' => 'simple', 'Image à côté' => 'split'],
    ])]
    public string $layout = 'simple';

    #[FormField(options: ['label' => 'Titre'])]
    public string $title = 'Un titre fort';

    #[FormField(options: ['label' => 'Sous-titre', 'required' => false])]
    public string $subtitle = '';

    #[FormField(BuilderImageType::class, ['label' => 'Image'])]
    #[DependsOn(field: 'layout', values: ['split'])]
    public ?string $image = null;

    #[FormField(ButtonBlock::class, ['label' => 'Bouton', 'csrf_protection' => false])]
    #[Valid]
    public ButtonBlock $button;

    public function __construct()
    {
        $this->button = new ButtonBlock();
    }
}
