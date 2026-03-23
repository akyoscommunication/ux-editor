<?php

namespace Akyos\UXEditor\Form\Type;

use Akyos\UXEditor\Model\Component;
use Akyos\UXEditor\Service\EditorService;
use Symfony\Component\Form\AbstractType;
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

        $metadata = $this->editorService->getComponentMetadata($component->getType());
        if ($metadata === null) {
            throw new \InvalidArgumentException(\sprintf('Type de composant éditeur inconnu : "%s".', (string) $component->getType()));
        }

        $builder
            ->add('data', $metadata->formType, [
                'label' => false,
                'block_prefix' => 'component_data',
                'data' => $component->getData(),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
        $resolver->setRequired('component');
        $resolver->setAllowedTypes('component', Component::class);
    }
}
