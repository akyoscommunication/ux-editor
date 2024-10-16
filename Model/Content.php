<?php

namespace Akyos\UXEditor\Model;

class Content
{
    /**
     * @var Component[]
     */
    public array $components = [];

    public function setComponents(array $components): static
    {
        $this->components = $components;

        return $this;
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function addComponent(Component $component): static
    {
        $this->components[] = $component;

        return $this;
    }

    public function removeComponent(Component $component): static
    {
        $key = array_search($component, $this->components, true);
        if ($key !== false) {
            unset($this->components[$key]);
        }

        return $this;
    }
}
