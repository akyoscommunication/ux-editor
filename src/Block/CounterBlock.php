<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Block;

use Builder\PageBuilderBundle\Attribute\AsBuilderBlock;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsBuilderBlock(name: 'counter', label: 'Compteur', category: 'Interactif', icon: 'heroicons:hashtag')]
#[AsLiveComponent('builder:counter', template: '@PageBuilder/blocks/counter.html.twig')]
#[AsFormType]
class CounterBlock
{
    use DefaultActionTrait;

    #[LiveProp]
    #[FormField(options: ['label' => 'Valeur de départ'])]
    public int $start = 0;

    #[LiveProp]
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        ++$this->count;
    }
}
