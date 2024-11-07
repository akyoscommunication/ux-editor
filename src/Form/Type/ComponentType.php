<?php

namespace Akyos\UXEditor\Form\Type;

use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Model\Data;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComponentType extends AbstractType
{
    public function __construct(
        private EditorService $editorService,
    ){}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Component $component */
        $component = $options['component'];

        $metadata = $this->editorService->getComponentMetadata($component->getType());

        $builder
            ->add('data', $metadata->formType, [
                'block_prefix' => 'component_data',
                'data' => $component->getData(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'component' => Component::class,
        ]);
    }
}
