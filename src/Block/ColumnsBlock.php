<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

#[AsBuilderBlock(name: 'columns', label: 'Colonnes', category: 'Mise en page', icon: 'heroicons:view-columns', container: true)]
#[AsFormType]
class ColumnsBlock
{
    #[FormField(ChoiceType::class, [
        'label' => 'Nombre de colonnes',
        'choices' => ['2 colonnes' => 2, '3 colonnes' => 3],
    ])]
    public int $count = 2;
}
