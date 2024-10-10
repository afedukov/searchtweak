<?php

namespace App\DTO;

use App\Models\EvaluationKeyword;
use App\Services\Mapper\Document;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class ModelExecutionResult implements Arrayable
{
    public function __construct(
        private int $code,
        private string $message,
        private Collection $documents,
        private int $totalCount = EvaluationKeyword::TOTAL_COUNT_UNKNOWN,
        private string $response = '',
    ) {
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return Collection<Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function isSuccessful(): bool
    {
        return $this->code >= 200 && $this->code < 300;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'successful' => $this->isSuccessful(),
            'count' => $this->documents->count(),
            'total_count' => $this->totalCount,
            'documents' => $this->documents->toArray(),
            'documents_plural' => Str::plural('document', $this->documents->count()),
            'response' => $this->response,
        ];
    }
}
