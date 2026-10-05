<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Form\Extension;

use Builder\PageBuilderBundle\Attribute\Inspector;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * ponytail: one extension on every form, bails out unless a property has #[Inspector].
 */
final class InspectorExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $parent = $form->getParent();
        $dataClass = $parent?->getConfig()->getDataClass();
        if (\is_string($dataClass)) {
            $inspector = self::read($dataClass, $form->getName());
            if (null !== $inspector) {
                $view->vars['inspector_tab'] = $inspector->tab;
                $view->vars['inspector_column'] = max(1, $inspector->column);
                $view->vars['inspector_span'] = max(1, $inspector->span);
            }
        }

        $groups = self::groups($view);
        if (null !== $groups) {
            $view->vars['inspector_groups'] = $groups;
        }
    }

    /**
     * @return list<array{label: string, columns: int, fields: list<string>}>|null
     */
    private static function groups(FormView $view): ?array
    {
        $used = false;
        $named = false;
        foreach ($view->children as $child) {
            if (self::hidden($child) || !\array_key_exists('inspector_column', $child->vars)) {
                continue;
            }
            $used = true;
            if (null !== $child->vars['inspector_tab'] && '' !== $child->vars['inspector_tab']) {
                $named = true;
            }
        }
        if (!$used) {
            return null;
        }

        $groups = [];
        $index = [];
        foreach ($view->children as $name => $child) {
            if (self::hidden($child)) {
                continue;
            }
            $tab = $child->vars['inspector_tab'] ?? null;
            $label = \is_string($tab) && '' !== $tab ? $tab : ($named ? 'Général' : '');
            $column = (int) ($child->vars['inspector_column'] ?? 1);
            $span = (int) ($child->vars['inspector_span'] ?? 1);
            if (!isset($index[$label])) {
                $index[$label] = \count($groups);
                $groups[] = ['label' => $label, 'columns' => 1, 'fields' => []];
            }
            $i = $index[$label];
            $groups[$i]['fields'][] = $name;
            $groups[$i]['columns'] = max($groups[$i]['columns'], $column + $span - 1);
        }

        return $groups;
    }

    private static function read(string $class, string $property): ?Inspector
    {
        if (!class_exists($class) || !property_exists($class, $property)) {
            return null;
        }
        $attributes = (new \ReflectionProperty($class, $property))->getAttributes(Inspector::class);

        return $attributes ? $attributes[0]->newInstance() : null;
    }

    private static function hidden(FormView $view): bool
    {
        return \in_array('hidden', $view->vars['block_prefixes'] ?? [], true);
    }
}
