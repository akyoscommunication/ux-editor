<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Model\Content;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('UX:Editor:Render', template: '@UXEditor/components/Render.html.twig')]
final class Render
{
    public Content $content;
}
