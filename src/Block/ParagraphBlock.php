<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsBuilderBlock(name: 'paragraph', label: 'Paragraphe', category: 'Texte', icon: 'heroicons:bars-3-bottom-left')]
#[AsTwigComponent('builder:paragraph', template: '@PageBuilder/blocks/paragraph.html.twig')]
#[AsFormType]
class ParagraphBlock
{
    #[FormField(TextareaType::class, ['label' => 'Texte', 'attr' => ['rows' => 6]])]
    public string $text = 'Écrivez ici.';
}
