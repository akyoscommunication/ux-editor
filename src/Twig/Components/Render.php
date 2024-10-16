<?php

namespace Akyos\UXEditor\Twig\Components;

use Akyos\UXEditor\Model\Content;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Render
{
    public Content $content;
}
