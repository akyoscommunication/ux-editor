<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

/**
 * Recopie les variables du CSS front (`:root`, `.dark`) sur le canvas.
 * Tailwind les émet sur `:root` du site ; dans l’admin elles n’existent pas.
 *
 * ponytail: ne lit que les règles au premier niveau. Si un token passe dans
 * un `@layer` ou un `@media`, l’étendre dans skip/capture. `@theme` est ignoré
 * exprès : Tailwind inline déjà ces valeurs dans les utilitaires.
 */
final class CanvasTokenCss
{
    public function __construct(
        #[Autowire('%page_builder.canvas_stylesheet%')]
        private string $stylesheet,
    ) {
    }

    #[AsTwigFunction('builder_canvas_tokens', isSafe: ['html'])]
    public function twig(): string
    {
        if (!is_file($this->stylesheet)) {
            return '';
        }
        $css = file_get_contents($this->stylesheet);

        return \is_string($css) ? self::fromSource($css) : '';
    }

    public static function fromSource(string $css): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
        $chunks = [];
        $length = \strlen($css);
        $i = 0;
        while ($i < $length) {
            while ($i < $length && ctype_space($css[$i])) {
                ++$i;
            }
            if ($i >= $length) {
                break;
            }
            if ('@' === $css[$i]) {
                $i = self::skipAtRule($css, $i);
                continue;
            }
            $start = $i;
            while ($i < $length && '{' !== $css[$i]) {
                if ('"' === $css[$i] || "'" === $css[$i]) {
                    $i = self::skipString($css, $i);
                    continue;
                }
                ++$i;
            }
            if ($i >= $length) {
                break;
            }
            $selector = trim(substr($css, $start, $i - $start));
            $bodyStart = ++$i;
            $depth = 1;
            while ($i < $length && $depth > 0) {
                if ('"' === $css[$i] || "'" === $css[$i]) {
                    $i = self::skipString($css, $i);
                    continue;
                }
                if ('{' === $css[$i]) {
                    ++$depth;
                } elseif ('}' === $css[$i]) {
                    --$depth;
                }
                ++$i;
            }
            $body = substr($css, $bodyStart, $i - $bodyStart - 1);
            $scoped = self::scopeSelector($selector);
            if (null !== $scoped && !str_contains($body, '</')) {
                $chunks[] = $scoped.'{'.$body.'}';
            }
        }

        return implode("\n", $chunks);
    }

    private static function scopeSelector(string $selector): ?string
    {
        return match ($selector) {
            ':root' => '[data-canvas-root]',
            '.dark' => '[data-canvas-root].dark,.dark [data-canvas-root]',
            default => null,
        };
    }

    private static function skipAtRule(string $css, int $i): int
    {
        $length = \strlen($css);
        $depth = 0;
        while ($i < $length) {
            if ('"' === $css[$i] || "'" === $css[$i]) {
                $i = self::skipString($css, $i);
                continue;
            }
            if ('{' === $css[$i]) {
                ++$depth;
            } elseif ('}' === $css[$i]) {
                --$depth;
                ++$i;
                if (0 === $depth) {
                    return $i;
                }
                continue;
            } elseif (';' === $css[$i] && 0 === $depth) {
                return $i + 1;
            }
            ++$i;
        }

        return $i;
    }

    private static function skipString(string $css, int $i): int
    {
        $quote = $css[$i];
        $length = \strlen($css);
        ++$i;
        while ($i < $length) {
            if ('\\' === $css[$i]) {
                $i += 2;
                continue;
            }
            if ($css[$i] === $quote) {
                return $i + 1;
            }
            ++$i;
        }

        return $i;
    }
}
