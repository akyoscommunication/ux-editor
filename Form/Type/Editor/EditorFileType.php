<?php

namespace Akyos\UXEditor\Form\Type\Editor;

use Akyos\UXEditor\Form\DataTransformer\EditorEntityTransformer;
use Akyos\UXEditor\Form\DataTransformer\EditorFileTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;

class EditorFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EditorFileTransformer());
    }

    public function getParent()
    {
        return FileType::class;
    }
}
