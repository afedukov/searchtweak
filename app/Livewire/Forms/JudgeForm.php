<?php

namespace App\Livewire\Forms;

use App\Models\Judge;
use App\Models\Tag;
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

    public int $setting_batch_size = 0;

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
                Rule::requiredIf($this->judge === null),
                'nullable',
                'string',
                'max:1024',
            ],
            'prompt_binary' => ['required', 'string', 'max:16384'],
            'prompt_graded' => ['required', 'string', 'max:16384'],
            'prompt_detail' => ['required', 'string', 'max:16384'],
            'setting_batch_size' => ['required', 'integer', 'min:0', 'max:20'],
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

        $judge = Judge::create(
            $this->except(['tags', 'setting_batch_size']) + [
                Judge::FIELD_USER_ID => Auth::user()->id,
                Judge::FIELD_TEAM_ID => Auth::user()->current_team_id,
                Judge::FIELD_SETTINGS => [
                    Judge::SETTING_BATCH_SIZE => $this->setting_batch_size,
                ],
            ]
        );

        app(SyncTagsService::class)->syncTags($judge, $this->tags);

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $data = $this->except(['tags', 'setting_batch_size']);

        // Only update api_key if a new value is provided
        if (empty($data['api_key'])) {
            unset($data['api_key']);
        }

        $data[Judge::FIELD_SETTINGS] = array_merge($this->judge->settings ?? [], [
            Judge::SETTING_BATCH_SIZE => $this->setting_batch_size,
        ]);

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
}
