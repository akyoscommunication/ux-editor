<?php

namespace Akyos\UXEditor\Model;

/** @TODO: faire en sorte que ce soit configurable au niveau du yaml */
class Category
{
    public string $name;
    public string $icon;

    public function __construct(string $name, string $icon)
    {
        $this->name = $name;
        $this->icon = $icon;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
