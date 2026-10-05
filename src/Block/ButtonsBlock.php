<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsBuilderBlock(name: 'buttons', label: 'Boutons', category: 'Contenu', icon: 'heroicons:cursor-arrow-rays')]
#[AsTwigComponent('builder:buttons', template: '@PageBuilder/blocks/buttons.html.twig')]
#[AsFormType]
class ButtonsBlock
{
    #[FormField(LiveCollectionType::class, [
        'label' => 'Liens',
        'entry_type' => ButtonBlock::class,
        'entry_options' => ['label' => false],
        'allow_add' => true,
        'allow_delete' => true,
        'by_reference' => false,
        'button_add_options' => ['label' => 'Ajouter un bouton', 'attr' => ['class' => 'mt-2 rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white']],
        'button_delete_options' => ['label' => 'Retirer', 'attr' => ['class' => 'text-xs font-medium text-red-600']],
    ])]
    public array $items = [];
}
