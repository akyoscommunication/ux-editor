<?php

namespace Akyos\UXEditor\Form\Type\Editor;

use Akyos\UXEditor\Form\DataTransformer\EditorEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EditorEntityType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $em,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $class = $options['class'];
        $builder->addModelTransformer(new EditorEntityTransformer($this->em->getRepository($class)));
    }

    public function getParent()
    {
        return EntityType::class;
    }
}
