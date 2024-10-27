<?php

namespace App\Services\Models;

use App\Models\EvaluationMetric;
use Illuminate\Support\Arr;
use Livewire\Wireable;

class ModelMetric implements Wireable, \JsonSerializable
{
    private array $dataset = [];
    private string $color = '';

    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $scorerType,
        private readonly string $briefDescription,
        private readonly string $description,
        private readonly string $scaleType,
        private readonly ?EvaluationMetric $lastMetric = null,
        private readonly int $keywordsCount = 1,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBriefDescription(): string
    {
        return $this->briefDescription;
    }

    public function setDataset(array $dataset): ModelMetric
    {
        $this->dataset = $dataset;

        return $this;
    }

    public function getDataset(): array
    {
        return $this->dataset;
    }

    public function getScorerType(): string
    {
        return $this->scorerType;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getScaleType(): string
    {
        return $this->scaleType;
    }

    public function getLastDatasetItem(): ?array
    {
        return Arr::last($this->dataset);
    }

    public function toLivewire(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'scaleType' => $this->scorerType,
            'briefDescription' => $this->briefDescription,
            'description' => $this->description,
            'dataset' => $this->dataset,
            'color' => $this->color,
            'keywordsCount' => $this->keywordsCount,
        ];
    }

    public static function fromLivewire($value): static
    {
        return (new static(
                id: $value['id'],
                name: $value['name'],
                scorerType: $value['scorerType'],
                briefDescription: $value['briefDescription'],
                description: $value['description'],
                scaleType: $value['scaleType'],
                keywordsCount: $value['keywordsCount'] ?? 1,
            ))
            ->setDataset($value['dataset'])
            ->setColor($value['color']);
    }

    public function jsonSerialize(): array
    {
        return $this->toLivewire();
    }

    public function getLastMetric(): ?EvaluationMetric
    {
        return $this->lastMetric;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): ModelMetric
    {
        $this->color = $color;

        return $this;
    }

    public function getKeywordsCount(): int
    {
        return $this->keywordsCount;
    }
}
