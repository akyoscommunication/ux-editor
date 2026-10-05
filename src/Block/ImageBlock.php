<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Builder\PageBuilderBundle\Attribute\DependsOn;
use Builder\PageBuilderBundle\Form\Type\BuilderImageType;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsBuilderBlock(name: 'image', label: 'Image', category: 'Média', icon: 'heroicons:photo')]
#[AsTwigComponent('builder:image', template: '@PageBuilder/blocks/image.html.twig')]
#[AsFormType]
class ImageBlock
{
    #[FormField(BuilderImageType::class, ['label' => 'Fichier'])]
    public ?string $image = null;

    #[FormField(options: ['label' => 'Texte alternatif', 'required' => false])]
    public string $alt = '';

    #[FormField(options: ['label' => 'Légende', 'required' => false])]
    #[DependsOn(field: 'image', notEmpty: true)]
    public ?string $caption = null;
}
