<?php

namespace Akyos\UXEditor\Form\Type;

use Akyos\UXEditor\Model\Editor\Component;
use Akyos\UXEditor\Model\Editor\Data;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
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

        $data = $this->editorService->getFieldsDefaultDataForComponent($component, $component->getData());

        $builder
            ->add('data', CollectionType::class, [
                'entry_type' => DataType::class,
                'entry_options' => [
                    'label' => false,
                    'component' => $component,
                ],
                'attr' => [
                    'class' => 'grid grid-cols-12 gap-4',
                ],
                'data' => $data,
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
