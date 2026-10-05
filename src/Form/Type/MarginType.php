<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Type;

use Builder\PageBuilderBundle\Model\Margin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MarginType extends AbstractType
{
    private const LABELS = [
        'top' => 'Haut',
        'right' => 'Droite',
        'bottom' => 'Bas',
        'left' => 'Gauche',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = Margin::normalize($options['data'] ?? null);
        foreach (self::LABELS as $side => $label) {
            $builder->add($side, IntegerType::class, [
                'label' => $label,
                'block_prefix' => 'builder_margin_side',
                'empty_data' => '0',
                'data' => $data[$side],
                'attr' => ['min' => 0, 'max' => 128, 'step' => 4],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'data' => Margin::zero(),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'builder_margin';
    }
}
