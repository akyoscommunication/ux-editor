<?php

namespace Akyos\UXEditor;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UXEditorBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
