<?php

namespace App\DTO;

use Livewire\Wireable;

class OrderBy implements Wireable
{
    public const int ORDER_BY_DEFAULT = 0;
    public const int ORDER_BY_KEYWORD = -1;

    public function __construct(public int $metricId = self::ORDER_BY_DEFAULT, public string $direction = 'asc')
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Invalid direction');
        }
    }

    public function getMetricId(): int
    {
        return $this->metricId;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function isDefaultBy(): bool
    {
        return $this->metricId === self::ORDER_BY_DEFAULT;
    }

    public function isDefaultDirection(): bool
    {
        return $this->direction === 'asc';
    }

    public function isDefault(): bool
    {
        return $this->isDefaultBy() && $this->isDefaultDirection();
    }

    public function setMetricId(int $metricId): OrderBy
    {
        $this->metricId = $metricId;

        return $this;
    }

    public function setDirection(string $direction): OrderBy
    {
        $this->direction = $direction;

        return $this;
    }

    public function toLivewire(): array
    {
        return [
            'metricId' => $this->getMetricId(),
            'direction' => $this->getDirection(),
        ];
    }

    public static function fromLivewire($value): static
    {
        return new static($value['metricId'], $value['direction']);
    }
}
