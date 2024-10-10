<?php

namespace App\Livewire\Forms;

use App\Models\SearchEndpoint;
use App\Rules\MapperCodeRule;
use App\Services\Endpoints\CustomHeadersService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EndpointForm extends Form
{
    public ?SearchEndpoint $endpoint = null;

    public string $name = '';

    public string $description = '';

    public string $method = 'GET';

    public string $url = '';

    public int $type = SearchEndpoint::TYPE_SEARCH_API;

    public int $mapper_type = SearchEndpoint::MAPPER_TYPE_DOT_ARRAY;

    public string $mapper_code = "id: data.items.*.id\nname: data.items.*.name\nimage: data.items.*.image";

    public string $headers = '';

    public int $setting_mt = SearchEndpoint::MULTI_THREADING_AUTO;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::unique('search_endpoints', SearchEndpoint::FIELD_NAME)
                    ->where(SearchEndpoint::FIELD_TEAM_ID, Auth::user()->currentTeam->id)
                    ->ignore($this->endpoint),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'method' => ['required', Rule::in(SearchEndpoint::VALID_METHODS)],
            'url' => ['required', 'string', 'url', 'max:4096'],
            'type' => ['required', 'integer', Rule::in([SearchEndpoint::TYPE_SEARCH_API])],
            'mapper_type' => ['required', 'integer', Rule::in([SearchEndpoint::MAPPER_TYPE_DOT_ARRAY])],
            'mapper_code' => ['required', 'string', 'max:4096', new MapperCodeRule()],
            'headers' => ['nullable', 'string', 'max:4096'],
            'setting_mt' => ['required', 'integer', Rule::in([SearchEndpoint::MULTI_THREADING_AUTO, SearchEndpoint::MULTI_THREADING_SINGLE])],
        ];
    }

    public function messages(): array
    {
        return [
            'setting_mt.required' => 'The multi-threading setting is required.',
            'setting_mt.integer' => 'The multi-threading setting must be an integer.',
            'setting_mt.in' => 'The selected multi-threading setting is invalid.',
        ];
    }

    public function store(): void
    {
        $this->validate();

        SearchEndpoint::create(
            $this->except('headers') + [
                SearchEndpoint::FIELD_HEADERS => app(CustomHeadersService::class)->composeHeadersArray($this->headers),
                SearchEndpoint::FIELD_USER_ID => Auth::user()->id,
                SearchEndpoint::FIELD_TEAM_ID => Auth::user()->currentTeam->id,
                SearchEndpoint::FIELD_SETTINGS => [
                    SearchEndpoint::SETTING_MULTI_THREADING => $this->setting_mt,
                ],
            ]
        );

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $this->endpoint->update(
            $this->except('headers') + [
                SearchEndpoint::FIELD_HEADERS => app(CustomHeadersService::class)->composeHeadersArray($this->headers),
                SearchEndpoint::FIELD_SETTINGS => [
                    SearchEndpoint::SETTING_MULTI_THREADING => $this->setting_mt,
                ],
            ]
        );

        $this->reset();
    }

    public function setEndpoint(SearchEndpoint $endpoint, bool $clone = false): void
    {
        $this->endpoint = $endpoint;

        $values = [
            'headers' => app(CustomHeadersService::class)->decomposeHeadersArray($endpoint->headers),
            'setting_mt' => $endpoint->getMultiThreadingSetting(),
        ] + $endpoint->toArray();

        if ($clone) {
            unset($values[SearchEndpoint::FIELD_ID]);
            $values['name'] .= ' clone';

            $this->endpoint = null;
        }

        $this->fill($values);
    }
}
