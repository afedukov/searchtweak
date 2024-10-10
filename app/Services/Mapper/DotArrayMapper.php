<?php

namespace App\Services\Mapper;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class DotArrayMapper implements MapperInterface
{
    protected const string TERM_DATA = 'data';
    protected const string TERM_ID = 'id';
    protected const string TERM_NAME = 'name';
    protected const string TERM_IMAGE = 'image';

    protected const array REQUIRED_ATTRIBUTES = [
        self::TERM_ID,
        self::TERM_NAME,
    ];

    protected ExpressionLanguage $expressionLanguage;

    protected array $attributes = [];
    protected array $vars = [];
    protected array $evaluatedVars = [];

    protected string $error = '';

    /** @var array<Document> */
    protected array $documents = [];

    public function __construct(protected readonly string $mapperCode)
    {
        $this->expressionLanguage = app(ExpressionLanguage::class);
    }

    public function getAttributes(): array
    {
        return array_keys($this->attributes);
    }

    public function initialize(): static
    {
        $this->attributes = [];
        $this->vars = [];
        $this->evaluatedVars = [];

        $lines = explode("\n", $this->mapperCode);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) !== 2 || empty(trim($parts[0])) || empty(trim($parts[1]))) {
                continue;
            }

            $attribute = trim($parts[0]);
            $right = trim($parts[1]);

            $terms = $this->getTerms($right);

            foreach ($terms as $term) {
                if (Str::startsWith($term, self::TERM_DATA)) {
                    $varName = 'var' . count($this->vars);

                    $right = str_replace($term, $varName, $right);
                    $this->vars[$varName] = $term;
                }
            }

            $this->attributes[$attribute] = $right;
        }

        $this->attributes = array_filter($this->attributes);

        return $this;
    }

    protected function getTerms(string $string): array
    {
        $terms = array_values(array_filter(preg_split('/[\s\(\)]/', $string)));

        return array_map('trim', $terms);
    }

    public function validate(): bool
    {
        $this->error = '';

        foreach (self::REQUIRED_ATTRIBUTES as $attribute) {
            if (!array_key_exists($attribute, $this->attributes)) {
                $this->error = sprintf('Attribute "%s" is missing', $attribute);

                return false;
            }
        }

        return true;
    }

    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return Collection<Document>
     */
    public function getDocuments(string $content, int $limit): Collection
    {
        $this->documents = [];

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return collect();
        }

        $data = Arr::dot([self::TERM_DATA => $data]);

        $this->evaluatedVars = $this->evaluateVars($data);

        foreach ($this->attributes as $attribute => $expression) {
            foreach ($this->evaluatedVars as $key => $vars) {
                try {
                    $evaluatedValue = $this->expressionLanguage->evaluate($expression, $vars);

                    $this->updateDocument($key, $attribute, $evaluatedValue);
                } catch (\Throwable) {}
            }
        }

        $this->removeInvalidDocuments();
        $this->keepFirstDocuments($limit);
        $this->normalizeDocuments();
        $this->setDocumentPositions();

        return collect($this->documents);
    }

    protected function getPattern(string $path, bool $array = false): string
    {
        $path = preg_quote($path, '/');
        $path = str_replace('\*', '([0-9]+)', $path);

        if ($array) {
            return "/^$path\.\d+$/i";
        }

        return "/^$path$/i";
    }

    protected function evaluateVars(array $data): array
    {
        $evaluatedVars = [];

        foreach ($this->vars as $varName => $term) {
            $pattern = $this->getPattern($term);
            $arrayPattern = $this->getPattern($term, true);

            foreach ($data as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                // match strings
                if (preg_match($pattern, $key, $matches) &&
                    isset($matches[1]) &&
                    (is_string($value) || is_numeric($value))
                ) {
                    $evaluatedVars[$matches[1]][$varName] = $value;
                }

                // match arrays
                if (preg_match($arrayPattern, $key, $matches) &&
                    isset($matches[1]) &&
                    (is_string($value) || is_numeric($value))
                ) {
                    if (!isset($evaluatedVars[$matches[1]][$varName])) {
                        $evaluatedVars[$matches[1]][$varName] = [];
                    }

                    $evaluatedVars[$matches[1]][$varName][] = $value;
                }
            }
        }

        return $evaluatedVars;
    }

    private function updateDocument(string $key, string $attribute, string|array $value): void
    {
        if (!isset($this->documents[$key])) {
            $this->documents[$key] = new Document();
        }

        if ($attribute === self::TERM_ID) {
            is_string($value) && $this->documents[$key]->setId($value);
        } elseif ($attribute === self::TERM_NAME) {
            is_string($value) && $this->documents[$key]->setName($value);
        } elseif ($attribute === self::TERM_IMAGE) {
            is_string($value) && $this->documents[$key]->setImage($value);
        } else {
            $this->documents[$key]->setAttribute($attribute, $value);
        }
    }

    private function normalizeDocuments(): void
    {
        $this->documents = array_values($this->documents);
    }

    private function setDocumentPositions(): void
    {
        foreach ($this->documents as $index => $document) {
            $document->setPosition($index + 1);
        }
    }

    private function removeInvalidDocuments(): void
    {
        $this->documents = array_filter($this->documents, fn (Document $document) =>
            $document->getId() !== '' && $document->getName() !== ''
        );
    }

    private function keepFirstDocuments(int $limit): void
    {
        $this->documents = array_slice($this->documents, 0, $limit);
    }
}

