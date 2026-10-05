<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

final class EmptyInspectorType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'builder_empty_inspector';
    }
}
