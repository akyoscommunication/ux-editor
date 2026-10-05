<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Model;

/**
 * Ordered block tree stored as JSON.
 *
 * Column slots are a list, not an object: json_decode turns {"0":[]} into a list anyway.
 */
final class Document
{
    public static function create(string $type, array $props, bool $container, ?array $margin = null): array
    {
        $block = [
            'id' => bin2hex(random_bytes(8)),
            'type' => $type,
            'props' => $props,
            'margin' => null === $margin ? Margin::zero() : Margin::normalize($margin),
        ];
        if ($container) {
            $block['slots'] = array_fill(0, max(1, (int) ($props['count'] ?? 2)), []);
        }

        return $block;
    }

    public static function find(array $document, string $id): ?array
    {
        return self::locate($document['blocks'] ?? [], $id)['block'] ?? null;
    }

    public static function insert(array $document, array $block, ?string $parentId, ?int $slot, int $index): array
    {
        $document['blocks'] = self::insertInto($document['blocks'] ?? [], $block, $parentId, $slot, $index);

        return $document;
    }

    public static function remove(array $document, string $id): array
    {
        $document['blocks'] = self::removeFrom($document['blocks'] ?? [], $id);

        return $document;
    }

    public static function move(array $document, string $id, ?string $parentId, ?int $slot, int $index): array
    {
        $located = self::locate($document['blocks'] ?? [], $id);
        if (null === $located) {
            return $document;
        }

        $document = self::remove($document, $id);

        return self::insert($document, $located['block'], $parentId, $slot, $index);
    }

    public static function duplicate(array $document, string $id): array
    {
        $located = self::locate($document['blocks'] ?? [], $id);
        if (null === $located) {
            return $document;
        }

        return self::insert(
            $document,
            self::cloneBlock($located['block']),
            $located['parentId'],
            $located['slot'],
            $located['index'] + 1,
        );
    }

    public static function paste(array $document, array $block, string $afterId): array
    {
        $located = self::locate($document['blocks'] ?? [], $afterId);
        if (null === $located) {
            return $document;
        }

        return self::insert(
            $document,
            self::cloneBlock($block),
            $located['parentId'],
            $located['slot'],
            $located['index'] + 1,
        );
    }

    /**
     * @param callable(string): bool $isContainer
     */
    public static function normalize(array $document, callable $isContainer): array
    {
        $document['blocks'] = self::normalizeList($document['blocks'] ?? [], $isContainer);

        return $document;
    }

    /**
     * ponytail: 30 snapshots, structural edits only. Upgrade path: a redo stack if Ctrl+Shift+Z is needed.
     *
     * @param list<array<string, mixed>> $history
     * @param array<string, mixed>       $document
     *
     * @return list<array<string, mixed>>
     */
    public static function remember(array $history, array $document): array
    {
        $history[] = $document;
        if (\count($history) > 30) {
            $history = \array_slice($history, -30);
        }

        return $history;
    }

    public static function updateProps(array $document, string $id, array $props): array
    {
        $document['blocks'] = self::map($document['blocks'] ?? [], function (array $block) use ($id, $props): array {
            if (($block['id'] ?? null) === $id) {
                $block['props'] = $props;
            }

            return $block;
        });

        return $document;
    }

    public static function updateMargin(array $document, string $id, array $margin): array
    {
        $margin = Margin::normalize($margin);
        $document['blocks'] = self::map($document['blocks'] ?? [], function (array $block) use ($id, $margin): array {
            if (($block['id'] ?? null) === $id) {
                $block['margin'] = $margin;
            }

            return $block;
        });

        return $document;
    }

    private static function normalizeList(array $blocks, callable $isContainer): array
    {
        foreach ($blocks as $i => $block) {
            if ($isContainer($block['type'] ?? '')) {
                $count = max(1, (int) ($block['props']['count'] ?? 2));
                $slots = array_values($block['slots'] ?? []);
                $kept = [];
                $extra = [];
                foreach ($slots as $index => $children) {
                    $children = self::normalizeList($children, $isContainer);
                    if ($index < $count) {
                        $kept[] = $children;
                    } else {
                        array_push($extra, ...$children);
                    }
                }
                while (\count($kept) < $count) {
                    $kept[] = [];
                }
                if ($extra) {
                    $kept[$count - 1] = array_merge($kept[$count - 1], $extra);
                }
                $block['slots'] = $kept;
            } elseif (isset($block['slots'])) {
                foreach ($block['slots'] as $index => $children) {
                    $block['slots'][$index] = self::normalizeList($children, $isContainer);
                }
            }
            $blocks[$i] = $block;
        }

        return $blocks;
    }

    private static function map(array $blocks, callable $fn): array
    {
        foreach ($blocks as $i => $block) {
            if (isset($block['slots'])) {
                foreach ($block['slots'] as $index => $children) {
                    $block['slots'][$index] = self::map($children, $fn);
                }
            }
            $blocks[$i] = $fn($block);
        }

        return $blocks;
    }

    private static function insertInto(array $blocks, array $block, ?string $parentId, ?int $slot, int $index): array
    {
        if (null === $parentId || '' === $parentId) {
            array_splice($blocks, max(0, min($index, \count($blocks))), 0, [$block]);

            return $blocks;
        }

        foreach ($blocks as $i => $child) {
            if (($child['id'] ?? null) === $parentId) {
                $slotIndex = $slot ?? 0;
                $list = $child['slots'][$slotIndex] ?? [];
                array_splice($list, max(0, min($index, \count($list))), 0, [$block]);
                $child['slots'][$slotIndex] = $list;
                $blocks[$i] = $child;

                return $blocks;
            }
            if (!isset($child['slots'])) {
                continue;
            }
            foreach ($child['slots'] as $slotIndex => $children) {
                $child['slots'][$slotIndex] = self::insertInto($children, $block, $parentId, $slot, $index);
            }
            $blocks[$i] = $child;
        }

        return $blocks;
    }

    private static function removeFrom(array $blocks, string $id): array
    {
        $kept = [];
        foreach ($blocks as $block) {
            if (($block['id'] ?? null) === $id) {
                continue;
            }
            if (isset($block['slots'])) {
                foreach ($block['slots'] as $index => $children) {
                    $block['slots'][$index] = self::removeFrom($children, $id);
                }
            }
            $kept[] = $block;
        }

        return $kept;
    }

    /** @return array{block: array, parentId: ?string, slot: ?int, index: int}|null */
    private static function locate(array $blocks, string $id, ?string $parentId = null, ?int $slot = null): ?array
    {
        foreach ($blocks as $index => $block) {
            if (($block['id'] ?? null) === $id) {
                return [
                    'block' => $block,
                    'parentId' => $parentId,
                    'slot' => $slot,
                    'index' => (int) $index,
                ];
            }
            foreach ($block['slots'] ?? [] as $slotIndex => $children) {
                $found = self::locate($children, $id, (string) $block['id'], (int) $slotIndex);
                if (null !== $found) {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function cloneBlock(array $block): array
    {
        $block['id'] = bin2hex(random_bytes(8));
        if (isset($block['slots'])) {
            foreach ($block['slots'] as $index => $children) {
                $block['slots'][$index] = array_map(self::cloneBlock(...), $children);
            }
        }

        return $block;
    }
}
