<?php

declare(strict_types=1);

namespace Builder\PageBuilderBundle\Model;

final readonly class GeneratedBlock
{
    public function __construct(
        public string $blockName,
        public string $className,
        public string $php,
        public string $twig,
    ) {
    }
}
