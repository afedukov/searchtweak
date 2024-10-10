<?php

namespace App\Services\Mapper;

use Illuminate\Support\Collection;

interface MapperInterface
{
    public function initialize(): static;

    public function validate(): bool;

    public function getError(): string;

    /**
     * @return array<string>
     */
    public function getAttributes(): array;

    /**
     * @return Collection<Document>
     */
    public function getDocuments(string $content, int $limit): Collection;
}
