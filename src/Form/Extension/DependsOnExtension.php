<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Extension;

use Builder\PageBuilderBundle\Attribute\DependsOn;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * ponytail: one extension on every form, bails out unless the data class has #[DependsOn].
 * Upgrade path: tag one extension per block if this shows up in the profiler.
 */
final class DependsOnExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dataClass = $options['data_class'] ?? null;
        if (!\is_string($dataClass) || !$rules = self::rules($dataClass)) {
            return;
        }

        $sync = function (FormEvent $event) use ($rules, $dataClass): void {
            $values = $this->values($event->getData());
            if (null === $values) {
                return;
            }
            $this->sync($event->getForm(), $values, $rules, $dataClass);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $sync);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($rules, $dataClass, $sync): void {
            $sync($event);
            $submitted = $event->getData();
            if (!\is_array($submitted)) {
                return;
            }
            foreach ($rules as $field => $conditions) {
                if (!$this->visible($submitted, $conditions)) {
                    unset($submitted[$field]);
                }
            }
            $event->setData($submitted);
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($rules): void {
            $data = $event->getData();
            if (!\is_object($data)) {
                return;
            }
            $values = $this->values($data) ?? [];
            foreach ($rules as $field => $conditions) {
                if (!$this->visible($values, $conditions) && property_exists($data, $field)) {
                    $data->$field = $this->blank($data, $field);
                }
            }
        });
    }

    /** @param array<string, list<DependsOn>> $rules */
    private function sync(FormInterface $form, array $values, array $rules, string $dataClass): void
    {
        foreach ($rules as $field => $conditions) {
            $show = $this->visible($values, $conditions);
            if ($show && !$form->has($field)) {
                [$type, $options] = self::field($dataClass, $field);
                $form->add($field, $type, $options);
            } elseif (!$show && $form->has($field)) {
                $form->remove($field);
            }
        }
    }

    /** @param list<DependsOn> $conditions */
    private function visible(array $values, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $current = $values[$condition->field] ?? null;
            if ($condition->notEmpty && !$this->filled($current)) {
                return false;
            }
            if ($condition->values !== [] && !\in_array($this->scalar($current), array_map($this->scalar(...), $condition->values), true)) {
                return false;
            }
        }

        return true;
    }

    private function values(mixed $data): ?array
    {
        if (\is_array($data)) {
            return $data;
        }
        if (!\is_object($data)) {
            return null;
        }
        $values = [];
        foreach ((new \ReflectionObject($data))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $values[$property->getName()] = $property->getValue($data);
            }
        }

        return $values;
    }

    private function filled(mixed $value): bool
    {
        if (null === $value || '' === $value || [] === $value) {
            return false;
        }
        if (\is_object($value) && method_exists($value, 'getId')) {
            return null !== $value->getId();
        }

        return true;
    }

    private function scalar(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if (\is_object($value) && method_exists($value, 'getId')) {
            return (string) $value->getId();
        }
        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (null === $value) {
            return '';
        }

        return (string) $value;
    }

    private function blank(object $data, string $field): mixed
    {
        $type = (new \ReflectionProperty($data, $field))->getType();
        if (!$type instanceof \ReflectionNamedType || $type->allowsNull()) {
            return null;
        }

        return match ($type->getName()) {
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            'array' => [],
            default => '',
        };
    }

    /** @return array<string, list<DependsOn>> */
    private static function rules(string $class): array
    {
        static $cache = [];
        if (isset($cache[$class])) {
            return $cache[$class];
        }
        if (!class_exists($class)) {
            return $cache[$class] = [];
        }

        $rules = [];
        $reflection = new \ReflectionClass($class);
        while ($reflection) {
            foreach ($reflection->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
                    continue;
                }
                $attributes = $property->getAttributes(DependsOn::class);
                if ($attributes) {
                    $rules[$property->getName()] = array_map(static fn (\ReflectionAttribute $attribute): DependsOn => $attribute->newInstance(), $attributes);
                }
            }
            $reflection = $reflection->getParentClass() ?: null;
        }

        return $cache[$class] = $rules;
    }

    /** @return array{0: ?string, 1: array<string, mixed>} */
    private static function field(string $class, string $name): array
    {
        $property = new \ReflectionProperty($class, $name);
        $attribute = $property->getAttributes(FormField::class)[0]->newInstance();

        return [$attribute->type, $attribute->options];
    }
}
