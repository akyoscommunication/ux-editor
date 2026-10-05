<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Builder\PageBuilderBundle\Attribute\Inspector;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsBuilderBlock(name: 'heading', label: 'Titre', category: 'Texte', icon: 'heroicons:document-text')]
#[AsTwigComponent('builder:heading', template: '@PageBuilder/blocks/heading.html.twig')]
#[AsFormType]
class HeadingBlock
{
    #[FormField(options: ['label' => 'Texte'])]
    #[NotBlank]
    #[Inspector(tab: 'Texte', column: 1)]
    public string $text = 'Titre';

    #[FormField(ChoiceType::class, [
        'label' => 'Niveau',
        'choices' => ['Titre 1' => 'h1', 'Titre 2' => 'h2', 'Titre 3' => 'h3'],
    ])]
    #[Inspector(tab: 'Texte', column: 2)]
    public string $level = 'h2';
}
