<?php

namespace App\Livewire\Forms;

use App\Models\Judge;
use App\Models\Tag;
use App\Services\Judges\JudgeParamsService;
use App\Services\SyncTagsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class JudgeForm extends Form
{
    public ?Judge $judge = null;

    public string $name = '';

    public string $description = '';

    public string $provider = Judge::PROVIDER_OPENAI;

    public string $model_name = '';

    public string $api_key = '';

    public string $prompt_binary = '';

    public string $prompt_graded = '';

    public string $prompt_detail = '';

    public int $setting_batch_size = Judge::DEFAULT_BATCH_SIZE;

    public string $model_params = '';

    public string $setting_base_url = '';

    public array $tags = [];

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                Rule::unique('judges', Judge::FIELD_NAME)
                    ->where(Judge::FIELD_TEAM_ID, Auth::user()->currentTeam->id)
                    ->ignore($this->judge),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'provider' => ['required', 'string', Rule::in(Judge::VALID_PROVIDERS)],
            'model_name' => ['required', 'string', 'max:255'],
            'api_key' => [
                Rule::requiredIf(fn (): bool => Judge::providerRequiresApiKey($this->provider) && (
                    $this->judge === null || empty($this->judge->api_key)
                )),
                'nullable',
                'string',
                'max:1024',
            ],
            'prompt_binary' => ['required', 'string', 'max:16384'],
            'prompt_graded' => ['required', 'string', 'max:16384'],
            'prompt_detail' => ['required', 'string', 'max:16384'],
            'setting_batch_size' => ['required', 'integer', 'min:1', 'max:20'],
            'model_params' => ['nullable', 'string', 'max:4096'],
            'setting_base_url' => [
                Rule::requiredIf($this->provider === Judge::PROVIDER_CUSTOM_OPENAI),
                'nullable',
                'string',
                'max:1024',
                'url:http,https',
            ],
            'tags' => ['nullable', 'array'],
            'tags.*.id' => ['integer', Rule::exists('tags', Tag::FIELD_ID)->where(Tag::FIELD_TEAM_ID, Auth::user()->current_team_id)],
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

        $paramsService = app(JudgeParamsService::class);

        $judge = Judge::create(
            $this->except(['tags', 'setting_batch_size', 'model_params', 'setting_base_url']) + [
                Judge::FIELD_USER_ID => Auth::user()->id,
                Judge::FIELD_TEAM_ID => Auth::user()->current_team_id,
                Judge::FIELD_SETTINGS => $this->composeSettings($paramsService),
            ]
        );

        app(SyncTagsService::class)->syncTags($judge, $this->tags);

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $paramsService = app(JudgeParamsService::class);

        $data = $this->except(['tags', 'setting_batch_size', 'model_params', 'setting_base_url']);

        // Only update api_key if a new value is provided
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $data[Judge::FIELD_SETTINGS] = $this->composeSettings($paramsService);

        $this->judge->update($data);

        app(SyncTagsService::class)->syncTags($this->judge, $this->tags);

        $this->reset();
    }

    public function setJudge(Judge $judge, bool $clone = false): void
    {
        $this->judge = $judge->load('tags');

        $values = $judge->toArray();
        // Never expose api_key in the form
        $values['api_key'] = '';
        $values['setting_batch_size'] = $judge->getBatchSize();
        $values['model_params'] = app(JudgeParamsService::class)->decomposeParamsArray($judge->getModelParams());
        $values['setting_base_url'] = $judge->getBaseUrl() ?? '';

        if ($clone) {
            unset($values[Judge::FIELD_ID]);
            $values['name'] .= ' clone';

            $this->judge = null;
        }

        $this->fill($values);
    }

    public function initDefaults(): void
    {
        $this->prompt_binary = Judge::getDefaultPrompt('binary');
        $this->prompt_graded = Judge::getDefaultPrompt('graded');
        $this->prompt_detail = Judge::getDefaultPrompt('detail');
    }

    private function composeSettings(JudgeParamsService $paramsService): array
    {
        $settings = [
            Judge::SETTING_BATCH_SIZE => $this->setting_batch_size,
            Judge::SETTING_MODEL_PARAMS => $paramsService->composeParamsArray($this->model_params),
        ];

        $baseUrl = trim($this->setting_base_url);
        if ($baseUrl !== '' && in_array($this->provider, [Judge::PROVIDER_CUSTOM_OPENAI, Judge::PROVIDER_OLLAMA], true)) {
            $settings[Judge::SETTING_BASE_URL] = $baseUrl;
        }

        return $settings;
    }
}
