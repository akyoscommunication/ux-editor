<?php

namespace Akyos\UXEditor\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

class EditorFileTransformer implements DataTransformerInterface
{
    /**
     * @param string $value
     * @return mixed
     */
    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            return new File($value);
        }

        return $value;
    }

    /**
     * @param File $value
     * @return mixed
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof File) {
            return $value->getPathname();
        }

        return $value;
    }
}
