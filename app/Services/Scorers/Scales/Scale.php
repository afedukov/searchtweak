<?php

namespace App\Services\Scorers\Scales;

use JsonSerializable;

abstract class Scale implements JsonSerializable
{
    public const string SCALE_TYPE = '';

    abstract public function getName(): string;

    /**
     * @return array<int, string>
     */
    abstract public function getValues(): array;

    /**
     * Return prevailing value from the given grades. E.g. if there are more 'Relevant' grades than 'Irrelevant' grades,
     * the prevailing value is 'Relevant'. If there are equal number of 'Relevant' and 'Irrelevant' grades, return null.
     *
     * @param array $grades
     *
     * @return float|null
     */
    abstract public function getValue(array $grades): ?float;

    public function getType(): string
    {
        return static::SCALE_TYPE;
    }

    public function getScaleButtonComponent(): string
    {
        return sprintf('scales.%s.button', $this->getType());
    }

    public function getScaleSwitchComponent(): string
    {
        return sprintf('scales.%s.switch', $this->getType());
    }

    public function getScaleBadgeComponent(): string
    {
        return sprintf('scales.%s.badge', $this->getType());
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->getType(),
            'name' => $this->getName(),
            'values' => $this->getValues(),
        ];
    }
}
