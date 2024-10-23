<?php

namespace Akyos\UXEditor\Form\Type;

use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Model\Data;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataType extends AbstractType
{
    public function __construct(
        private EditorService $editorService,
    ){}

    // give metadata to the form view
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['metadata'] = $this->editorService->getDataFieldMetadata($options['component'], $form->getData());
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Component $component */
        $component = $options['component'];

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (PreSetDataEvent $event) use ($component) {
                    /** @var Data $data */
                    $data = $event->getData();
                    $form = $event->getForm();
                    $metadata = $this->editorService->getDataFieldMetadata($component, $data);

                    if (isset($metadata['type'])) {
                        $form
//                            ->add('name', HiddenType::class)
                            ->add('value', $metadata['type'], array_merge([
                                'block_prefix' => 'editor_data_value',
                                'empty_data' => $data->getValue(),

                            ], $metadata['typeOpts']))
                        ;
                    }
                }
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Data::class,
            'component' => Component::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'editor_data';
    }
}
