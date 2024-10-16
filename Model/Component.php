<?php

namespace Akyos\UXEditor\Model;

class Component implements \Stringable
{
    public string $id = '';

    /**
     * slug of twig component
     */
    public ?string $type = null;

    /**
     * @var Data[] $data
     */
    public array $data = [];

    /**
     * @var Component[] $children
     */
    public array $children = [];

    public ?int $order = 0;

    public function __construct(
    )
    {
        $this->id = str_replace('.', '_', uniqid('component_', true));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function addData(Data $data)
    {
        $this->data[$data->getName()] = $data;

        return $this;
    }

    public function getChildren(): ?array
    {
        return $this->children;
    }

    public function setChildren(?array $children): static
    {
        $this->children = $children;

        return $this;
    }

    public function addChild(Component $component): string
    {
        $findedChild = array_filter($this->children, fn($child) => $child->getId() === $component->getId());
        if (empty($findedChild)) {
            $this->children[] = $component;
        }

        return $this;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function __toString()
    {
        return (string)$this->type;
    }
}
