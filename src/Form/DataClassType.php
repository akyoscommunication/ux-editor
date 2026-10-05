<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type built from a class marked with #[AsFormType].
 * ponytail: Symfony 8.1 has no DataClassType; drop this when the app is on 8.2.
 */
final class DataClassType extends AbstractType
{
    /**
     * @param class-string $dataClass
     * @param class-string $parent
     * @param array<string, array{0: class-string|null, 1: array<string, mixed>}> $fields
     * @param array<string, mixed> $options
     */
    public function __construct(
        private string $dataClass,
        private string $parent,
        private string $blockPrefix,
        private array $fields,
        private array $options,
    ) {
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults($this->options + ['data_class' => $this->dataClass]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->fields as $name => [$type, $fieldOptions]) {
            $builder->add($name, $type, $fieldOptions);
        }
    }

    public function getBlockPrefix(): string
    {
        return $this->blockPrefix;
    }
}
