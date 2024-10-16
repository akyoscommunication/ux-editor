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

        // transform to File object, $value is path to file
        return new File($value);
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

        // transform to string, $value is File object
        return $value->getPathname();
    }
}
