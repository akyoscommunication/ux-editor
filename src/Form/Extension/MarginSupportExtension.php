<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Extension;

use Builder\PageBuilderBundle\Form\Type\MarginType;
use Builder\PageBuilderBundle\Model\Margin;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ponytail: margin is a block support, injected only when the inspector passes builder_margin.
 */
final class MarginSupportExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['builder_margin' => null]);
        $resolver->setAllowedTypes('builder_margin', ['null', 'array']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!\is_array($options['builder_margin'])) {
            return;
        }

        $margin = Margin::normalize($options['builder_margin']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($margin): void {
            $event->getForm()->add('margin', MarginType::class, [
                'mapped' => false,
                'label' => false,
                'data' => $margin,
            ]);
        }, -100);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$form->getConfig()->getType()->getInnerType() instanceof MarginType) {
            return;
        }

        $view->vars['inspector_tab'] = 'Marges';
        $view->vars['inspector_column'] = 1;
        $view->vars['inspector_span'] = 2;
    }
}
