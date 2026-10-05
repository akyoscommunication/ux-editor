<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsBuilderBlock(name: 'button', label: 'Bouton', category: 'Contenu', icon: 'heroicons:link')]
#[AsTwigComponent('builder:button', template: '@PageBuilder/blocks/button.html.twig')]
#[AsFormType]
class ButtonBlock
{
    #[FormField(options: ['label' => 'Libellé'])]
    public ?string $label = 'En savoir plus';

    #[FormField(options: ['label' => 'Lien', 'required' => false])]
    public ?string $url = '';

    #[FormField(ChoiceType::class, [
        'label' => 'Style',
        'choices' => ['Primaire' => 'primary', 'Secondaire' => 'secondary'],
    ])]
    public ?string $style = 'primary';
}
