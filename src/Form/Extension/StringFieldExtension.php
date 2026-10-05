<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * TextareaType turns an empty value into null, and #[AsFormType] never sets empty_data to ''.
 */
final class StringFieldExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            if (null !== $event->getData()) {
                return;
            }
            $form = $event->getForm();
            if ($form->getConfig()->getCompound()) {
                return;
            }
            $class = $form->getParent()?->getConfig()->getDataClass();
            $property = (string) $form->getPropertyPath();
            if (!\is_string($class) || '' === $property || !property_exists($class, $property)) {
                return;
            }
            $type = (new \ReflectionProperty($class, $property))->getType();
            if ($type instanceof \ReflectionNamedType && 'string' === $type->getName() && !$type->allowsNull()) {
                $event->setData('');
            }
        });
    }
}
