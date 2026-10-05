<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Service;

use Builder\PageBuilderBundle\Model\GeneratedBlock;

final class BlockFromComponentGenerator
{
    public function generate(string $component, string $source, string $namespace): ?GeneratedBlock
    {
        $component = str_replace('/', ':', trim($component));
        $props = $this->props($source);
        $editable = $this->editableContent($source);
        $hasDefaultContent = $this->hasContentBlock($source) && !$editable;
        if ([] === $props && !$editable && !$hasDefaultContent) {
            return null;
        }

        $blockName = $this->blockName($component);
        $className = $this->className($component);
        $fields = [];
        foreach ($props as $prop) {
            if ('content' === $prop['name']) {
                continue;
            }
            $fields[] = $prop;
        }

        return new GeneratedBlock(
            $blockName,
            $className,
            $this->phpFile($namespace, $className, $blockName, $component, $fields, $editable),
            $this->twigFile($component, $fields, $editable),
        );
    }

    /**
     * @param list<array{name: string, kind: string, default: mixed, choices: list<string>, help: string}> $fields
     */
    private function phpFile(string $namespace, string $className, string $blockName, string $label, array $fields, bool $editable): string
    {
        $uses = [
            'Builder\\PageBuilderBundle\\Attribute\\AsBuilderBlock',
            'Symfony\\Component\\Form\\Attribute\\AsFormType',
            'Symfony\\Component\\Form\\Attribute\\FormField',
        ];
        $kinds = array_column($fields, 'kind');
        if (\in_array('choice', $kinds, true)) {
            $uses[] = 'Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType';
        }
        if (\in_array('boolean', $kinds, true)) {
            $uses[] = 'Symfony\\Component\\Form\\Extension\\Core\\Type\\CheckboxType';
        }
        if (\in_array('text', $kinds, true)) {
            $uses[] = 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType';
        }
        if ($editable) {
            $uses[] = 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType';
        }
        $uses[] = 'Symfony\\UX\\TwigComponent\\Attribute\\AsTwigComponent';

        $body = [];
        foreach ($fields as $prop) {
            $options = [
                'label' => $prop['name'],
                'required' => false,
            ];
            if ('' !== $prop['help']) {
                $options['help'] = $prop['help'];
            }
            $type = 'TextType::class';
            if ('choice' === $prop['kind']) {
                $type = 'ChoiceType::class';
                $options['choices'] = array_combine($prop['choices'], $prop['choices']);
            } elseif ('boolean' === $prop['kind']) {
                $type = 'CheckboxType::class';
            }
            $default = 'boolean' === $prop['kind']
                ? ($prop['default'] ? 'true' : 'false')
                : $this->php(null === $prop['default'] ? null : (string) $prop['default']);
            $phpType = 'boolean' === $prop['kind'] ? 'bool' : '?string';
            $body[] = '    #[FormField('.$type.', '.$this->php($options, 1).")]\n    public {$phpType} \${$prop['name']} = {$default};";
        }
        if ($editable) {
            $body[] = "    #[FormField(TextareaType::class, ['label' => 'Contenu', 'required' => false])]\n    public ?string \$content = '';";
        }
        $properties = [] === $body ? '' : "\n".implode("\n\n", $body)."\n";
        $imports = implode("\n", array_map(static fn (string $use): string => 'use '.$use.';', $uses));

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace}\\Block;

{$imports}

#[AsBuilderBlock(name: {$this->php($blockName)}, label: {$this->php($label)}, category: 'Contenu')]
#[AsTwigComponent({$this->php('builder:'.$blockName)}, template: {$this->php('blocks/'.$blockName.'.html.twig')})]
#[AsFormType]
class {$className}
{{$properties}}

PHP;
    }

    /**
     * @param list<array{name: string, kind: string, default: mixed, choices: list<string>, help: string}> $fields
     */
    private function twigFile(string $component, array $fields, bool $editable): string
    {
        $pairs = array_map(static fn (array $prop): string => $prop['name'].': '.$prop['name'], $fields);
        $with = [] === $pairs ? '' : ' with {'.implode(', ', $pairs).'}';
        $open = "{% component '{$component}'{$with} %}";
        if (!$editable) {
            return $open."{% endcomponent %}\n";
        }

        return $open."\n    {% block content %}{{ content|default('') }}{% endblock %}\n{% endcomponent %}\n";
    }

    private function blockName(string $component): string
    {
        return implode('-', array_map(strtolower(...), explode(':', $component)));
    }

    private function className(string $component): string
    {
        return implode('', explode(':', $component)).'Block';
    }

    /**
     * @return list<array{name: string, kind: string, default: mixed, choices: list<string>, help: string}>
     */
    private function props(string $source): array
    {
        if (1 !== preg_match('/\{%-?\s*props\b(.*?)-?%\}/s', $source, $match) || !str_contains($match[1], '##')) {
            return [];
        }

        $kind = 'text';
        $choices = [];
        $help = '';
        $props = [];
        foreach (preg_split("/\r\n|\n|\r/", $match[1]) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            if (str_starts_with($line, '##')) {
                [$kind, $choices, $help] = $this->readDoc($line);
                continue;
            }
            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.+?)\s*,?\s*$/', $line, $field)) {
                continue;
            }
            $props[] = [
                'name' => $field[1],
                'kind' => $kind,
                'default' => $this->literal($field[2]),
                'choices' => $choices,
                'help' => $help,
            ];
            $kind = 'text';
            $choices = [];
            $help = '';
        }

        return $props;
    }

    private function editableContent(string $source): bool
    {
        if (!$this->hasContentBlock($source)) {
            return false;
        }
        preg_match('/\{%-?\s*block\s+content\b[^%]*-?%\}(.*?)\{%-?\s*endblock/s', $source, $block);
        $body = preg_replace('/\{#.*?#\}/s', '', $block[1] ?? '') ?? '';

        return '' === trim($body);
    }

    private function hasContentBlock(string $source): bool
    {
        return 1 === preg_match('/\{%-?\s*block\s+content\b/', $source);
    }

    /**
     * @return array{0: string, 1: list<string>, 2: string}
     */
    private function readDoc(string $line): array
    {
        $text = trim(substr($line, 2));
        if (preg_match('/^boolean\b\s*(.*)$/i', $text, $boolean)) {
            return ['boolean', [], trim($boolean[1])];
        }
        if (preg_match("/^('(?:[^']*)'(?:\\s*\\|\\s*'(?:[^']*)')*)\\s*(.*)$/", $text, $union)) {
            preg_match_all("/'([^']*)'/", $union[1], $choices);

            return ['choice', $choices[1], trim($union[2])];
        }

        return ['text', [], $text];
    }

    private function literal(string $raw): mixed
    {
        $raw = rtrim(trim($raw), ',');
        if ('true' === $raw) {
            return true;
        }
        if ('false' === $raw) {
            return false;
        }
        if ('null' === $raw) {
            return null;
        }
        if (preg_match('/^\'(.*)\'$/', $raw, $quoted) || preg_match('/^"(.*)"$/', $raw, $quoted)) {
            return $quoted[1];
        }

        return $raw;
    }

    private function php(mixed $value, int $indent = 0): string
    {
        if (!\is_array($value)) {
            return var_export($value, true);
        }
        if ([] === $value) {
            return '[]';
        }
        $pad = str_repeat('    ', $indent);
        $inner = str_repeat('    ', $indent + 1);
        $lines = ['['];
        foreach ($value as $key => $item) {
            $lines[] = $inner.$this->php($key).' => '.$this->php($item, $indent + 1).',';
        }
        $lines[] = $pad.']';

        return implode("\n", $lines);
    }
}
