<?php

namespace App\DTO;

readonly class MetricChange
{
    public function __construct(private int $change, private bool $showChangeValue) {
    }

    public function getChange(): int
    {
        return $this->change;
    }

    public function isShowChangeValue(): bool
    {
        return $this->showChangeValue;
    }
}
