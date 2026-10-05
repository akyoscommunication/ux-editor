<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Model;

final class Margin
{
    public const SIDES = ['top', 'right', 'bottom', 'left'];

    /** @return array{top: int, right: int, bottom: int, left: int} */
    public static function zero(): array
    {
        return ['top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0];
    }

    /** @return array{top: int, right: int, bottom: int, left: int} */
    public static function normalize(mixed $value): array
    {
        $out = self::zero();
        if (!\is_array($value)) {
            return $out;
        }
        foreach (self::SIDES as $side) {
            $n = (int) ($value[$side] ?? 0);
            $n = intdiv($n, 4) * 4;
            $out[$side] = max(0, min(128, $n));
        }

        return $out;
    }

    /** L’inspecteur affiche « Marges ». La clé enregistrée reste margin. */
    public static function style(mixed $value): string
    {
        $padding = self::normalize($value);

        return sprintf('padding: %dpx %dpx %dpx %dpx', $padding['top'], $padding['right'], $padding['bottom'], $padding['left']);
    }
}
