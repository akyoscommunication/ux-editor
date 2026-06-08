<?php

namespace Akyos\UXEditor\Form\Type\Editor;

use Akyos\UXEditor\Form\DataTransformer\EditorFileTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EditorFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EditorFileTransformer());
    }

    public function getBlockPrefix(): string
    {
        return 'ux_editor_file';
    }
}
