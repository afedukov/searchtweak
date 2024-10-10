<?php

namespace App\Services\Models;

use App\DTO\ModelExecutionResult;
use App\Models\EvaluationKeyword;
use App\Models\SearchModel;
use App\Services\Mapper\MapperFactory;
use App\Services\Mapper\MapperInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class ExecuteModelService
{
    public const string TERM_QUERY = '#query#';

    private SearchModel $model;
    private MapperInterface $mapper;

    private int $limit = 10;

    public function __construct(private readonly MapperFactory $factory, private readonly ClientInterface $client)
    {
    }

    public function initialize(SearchModel $model): self
    {
        $this->model = $model;

        $this->mapper = $this->factory
            ->create($model->endpoint->mapper_type, $model->endpoint->mapper_code)
            ->initialize();

        return $this;
    }

    public function execute(string $query): ModelExecutionResult
    {
        try {
            $response = $this->client
                ->request($this->model->endpoint->method, $this->model->endpoint->url, [
                    RequestOptions::TIMEOUT => 15,
                    RequestOptions::CONNECT_TIMEOUT => 10,
                    RequestOptions::READ_TIMEOUT => 15,
                    RequestOptions::HEADERS => $this->model->getHeaders() + $this->model->getHiddenHeaders(),
                    RequestOptions::QUERY => $this->composeQuery($query, $this->model->params),
                    RequestOptions::BODY => $this->composeBody($query, $this->model->body),
                ]);
        } catch (GuzzleException $e) {
            return new ModelExecutionResult(intval($e->getCode()), $e->getMessage(), new Collection());
        }

        $responseContent = $response->getBody()->getContents();

        $documents = $this->mapper->getDocuments($responseContent, $this->limit);

        $totalCount = $documents->isEmpty() ? 0 : EvaluationKeyword::TOTAL_COUNT_UNKNOWN;

        // test model mode
        if ($query === '') {
            $decodedResponse = json_decode($responseContent);

            // if $responseContent is valid json then prettify it
            if ($decodedResponse !== null) {
                $responseContent = json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        } else {
            // we don't need to store response content in normal execution mode
            $responseContent = '';
        }

        return new ModelExecutionResult($response->getStatusCode(), $response->getReasonPhrase(), $documents, $totalCount, $responseContent);
    }

    private function composeQuery(string $query, array $params): array
    {
        return array_filter(
            array_map(fn (string $value) => str_replace(self::TERM_QUERY, $query, $value), $params)
        );
    }

    private function composeBody(string $query, string $body): string
    {
        return str_replace(self::TERM_QUERY, $query, $body);
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }
}
