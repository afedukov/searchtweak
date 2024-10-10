<?php

namespace App\Livewire\Forms;

use App\Models\SearchEndpoint;
use App\Models\SearchModel;
use App\Models\Tag;
use App\Rules\EvaluationKeywordsRule;
use App\Rules\MapperCodeRule;
use App\Services\Endpoints\CustomHeadersService;
use App\Services\Evaluations\SyncKeywordsService;
use App\Services\Models\ExecuteModelService;
use App\Services\Models\RequestHeadersService;
use App\Services\SyncTagsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ModelForm extends Form
{
    public ?SearchModel $model = null;

    public string $name = '';

    public string $description = '';

    public string $endpoint_id = '';

    public string $params = 'q: ' . ExecuteModelService::TERM_QUERY;

    public string $body = '';

    public string $headers = '';

    public ?int $body_type = null;

    public int $mapper_type = SearchEndpoint::MAPPER_TYPE_DOT_ARRAY;

    public string $mapper_code = '';

    public array $tags = [];

    public string $keywords = '';

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::unique('search_models', SearchModel::FIELD_NAME)
                    ->where(SearchModel::FIELD_TEAM_ID, Auth::user()->currentTeam->id)
                    ->ignore($this->model),
            ],
            'endpoint_id' => [
                'required',
                Rule::exists('search_endpoints', SearchEndpoint::FIELD_ID),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'params' => ['nullable', 'string', 'max:8192'],
            'body' => ['nullable', 'string', 'max:8192'],
            'headers' => ['nullable', 'string', 'max:4096'],
            'body_type' => ['nullable', 'required_with:body', 'integer', Rule::in(array_keys(RequestHeadersService::getBodyTypes()))],
            'mapper_code' => ['required', 'string', 'max:4096', new MapperCodeRule()],
            'tags' => ['nullable', 'array'],
            'tags.*.id' => ['integer', Rule::exists('tags', Tag::FIELD_ID)->where(Tag::FIELD_TEAM_ID, Auth::user()->current_team_id)],
            'keywords' => ['nullable', 'string', new EvaluationKeywordsRule()],
        ];
    }

    public function messages(): array
    {
        return [
            'tags' => 'The selected tags are invalid.',
            'tags.*.id' => 'The selected tag is invalid.',
        ];
    }

    public function store(): void
    {
        $this->validate();

        $model = SearchModel::create(
            $this->except(['headers', 'params', 'tags', 'keywords']) + [
                SearchModel::FIELD_PARAMS => app(CustomHeadersService::class)->composeHeadersArray($this->params),
                SearchModel::FIELD_HEADERS => app(CustomHeadersService::class)->composeHeadersArray($this->headers),
                SearchModel::FIELD_USER_ID => Auth::user()->id,
                SearchModel::FIELD_TEAM_ID => Auth::user()->current_team_id,
                SearchModel::FIELD_SETTINGS => [
                    SearchModel::SETTING_KEYWORDS => SyncKeywordsService::getKeywordsFromString($this->keywords)->all(),
                ],
            ]
        );

        app(SyncTagsService::class)->syncTags($model, $this->tags);

        $this->updateEndpointMapper($model);
        $model->endpoint->save();

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $this->model->update(
            $this->except(['headers', 'params', 'tags', 'keywords']) + [
                SearchModel::FIELD_PARAMS => app(CustomHeadersService::class)->composeHeadersArray($this->params),
                SearchModel::FIELD_HEADERS => app(CustomHeadersService::class)->composeHeadersArray($this->headers),
                SearchModel::FIELD_SETTINGS => [
                    SearchModel::SETTING_KEYWORDS => SyncKeywordsService::getKeywordsFromString($this->keywords)->all(),
                ],
            ]
        );

        app(SyncTagsService::class)->syncTags($this->model, $this->tags);

        $this->updateEndpointMapper($this->model);
        $this->model->endpoint->save();

        $this->reset();
    }

    public function getModel(): SearchModel
    {
        $this->validate();

        $model = (new SearchModel())->fill(
            $this->except(['headers', 'params', 'keywords']) + [
            SearchModel::FIELD_PARAMS => app(CustomHeadersService::class)->composeHeadersArray($this->params),
            SearchModel::FIELD_HEADERS => app(CustomHeadersService::class)->composeHeadersArray($this->headers),
            SearchModel::FIELD_SETTINGS => [
                SearchModel::SETTING_KEYWORDS => SyncKeywordsService::getKeywordsFromString($this->keywords)->all(),
            ],
        ]);

        $this->updateEndpointMapper($model);

        return $model;
    }

    private function updateEndpointMapper(SearchModel $model): void
    {
        $model->endpoint->mapper_type = $this->mapper_type;
        $model->endpoint->mapper_code = $this->mapper_code;
    }

    public function setModel(SearchModel $model, bool $clone = false): void
    {
        $this->model = $model->load('tags');

        $values = [
            'params' => app(CustomHeadersService::class)->decomposeHeadersArray($model->params),
            'headers' => app(CustomHeadersService::class)->decomposeHeadersArray($model->headers),
            'mapper_code' => $model->endpoint->mapper_code,
            'keywords' => implode("\n", $model->getKeywords()),
        ] + $model->toArray();

        if ($clone) {
            unset($values[SearchModel::FIELD_ID]);
            $values['name'] .= ' clone';

            $this->model = null;
        }

        $this->fill($values);
    }
}
