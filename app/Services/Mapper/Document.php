<?php

namespace App\Services\Mapper;

use Illuminate\Contracts\Support\Arrayable;

class Document implements Arrayable
{
    protected string $id = '';
    protected string $name = '';
    protected ?string $image = null;

    protected int $position = 0;

    /** @var array<string, string|array> */
    protected array $attributes = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Document
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Document
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): Document
    {
        $this->image = $image;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): Document
    {
        $this->position = $position;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): Document
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function setAttribute(string $key, string|array $value): Document
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'position' => $this->position,
            'attributes' => $this->attributes,
        ];
    }

    public static function createFromArray(array $data): Document
    {
        return (new Document())
            ->setId($data['id'])
            ->setName($data['name'])
            ->setImage($data['image'] ?? null)
            ->setPosition($data['position'])
            ->setAttributes($data['attributes'] ?? []);
    }
}
