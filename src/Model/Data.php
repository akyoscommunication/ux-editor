<?php

namespace Akyos\UXEditor\Model;

class Data
{
    private ?string $name;

    private mixed $value;

    public function __construct(?string $name = null, mixed $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }
}
