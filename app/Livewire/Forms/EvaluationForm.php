<?php

namespace App\Livewire\Forms;

use App\Http\Requests\StoreEvaluationRequest;
use App\Jobs\Evaluations\RecalculateMetricsJob;
use App\Models\EvaluationKeyword;
use App\Models\EvaluationMetric;
use App\Models\SearchEndpoint;
use App\Models\SearchEvaluation;
use App\Rules\EvaluationKeywordsRule;
use App\Services\Evaluations\SyncKeywordsService;
use App\Services\Evaluations\SyncMetricsService;
use App\Services\Scorers\ScorerFactory;
use App\Services\SyncTagsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EvaluationForm extends Form
{
    public ?SearchEvaluation $evaluation = null;

    public bool $cloneModelTags = true;

    public string $model_id = '';

    public string $name = '';

    public string $description = '';

    public string $keywords = '';

    public int $status = SearchEvaluation::STATUS_PENDING;

    public array $metrics = [];

    public int $setting_feedback_strategy = 1;

    public int $setting_reuse_strategy = SearchEvaluation::REUSE_STRATEGY_NONE;

    public bool $setting_show_position = false;

    public bool $setting_auto_restart = false;

    public array $tags = [];

    public function rules(): array
    {
        return StoreEvaluationRequest::getValidationRules() + [
            'status' => ['required', 'integer', Rule::in([SearchEvaluation::STATUS_PENDING])],
            'keywords' => ['required', 'string', new EvaluationKeywordsRule()],
        ];
    }

    public function messages(): array
    {
        return StoreEvaluationRequest::getValidationMessages();
    }

    public function store(): void
    {
        $this->validate();

        $evaluation = SearchEvaluation::create(
            $this->except(['keywords', 'metrics', 'tags']) + [
                SearchEndpoint::FIELD_USER_ID => Auth::user()->id,
                SearchEvaluation::FIELD_STATUS => SearchEvaluation::STATUS_PENDING,
                SearchEvaluation::FIELD_SCALE_TYPE => ScorerFactory::create(Arr::first($this->metrics)[EvaluationMetric::FIELD_SCORER_TYPE])->getScale()->getType(),
                SearchEvaluation::FIELD_SETTINGS => [
                    SearchEvaluation::SETTING_FEEDBACK_STRATEGY => $this->setting_feedback_strategy,
                    SearchEvaluation::SETTING_SHOW_POSITION => $this->setting_show_position,
                    SearchEvaluation::SETTING_REUSE_STRATEGY => $this->setting_reuse_strategy,
                    SearchEvaluation::SETTING_AUTO_RESTART => $this->setting_auto_restart,
                ],
            ]
        );

        app(SyncKeywordsService::class)->syncString($evaluation, $this->keywords);
        app(SyncMetricsService::class)->sync($evaluation, $this->metrics);
        app(SyncTagsService::class)->syncTags($evaluation, $this->tags);

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $this->evaluation->update(
            $this->except(['keywords', 'metrics', 'tags']) + [
                SearchEvaluation::FIELD_SETTINGS => [
                    SearchEvaluation::SETTING_FEEDBACK_STRATEGY => $this->setting_feedback_strategy,
                    SearchEvaluation::SETTING_SHOW_POSITION => $this->setting_show_position,
                    SearchEvaluation::SETTING_REUSE_STRATEGY => $this->setting_reuse_strategy,
                    SearchEvaluation::SETTING_AUTO_RESTART => $this->setting_auto_restart,
                ],
            ]
        );

        app(SyncKeywordsService::class)->syncString($this->evaluation, $this->keywords);
        app(SyncMetricsService::class)->sync($this->evaluation, $this->metrics);
        app(SyncTagsService::class)->syncTags($this->evaluation, $this->tags);

        foreach ($this->evaluation->keywords as $keyword) {
            RecalculateMetricsJob::dispatch($keyword->id);
        }

        $this->reset();
    }

    public function setEvaluation(SearchEvaluation $evaluation, bool $clone = false): void
    {
        $this->cloneModelTags = false;

        $this->evaluation = $evaluation->load('tags');

        $values = [
                'keywords' => $evaluation->keywords->pluck(EvaluationKeyword::FIELD_KEYWORD)->implode("\n"),
                'metrics' => $evaluation->metrics->toArray(),
                'setting_feedback_strategy' => $evaluation->settings[SearchEvaluation::SETTING_FEEDBACK_STRATEGY] ?? 1,
                'setting_show_position' => $evaluation->settings[SearchEvaluation::SETTING_SHOW_POSITION] ?? false,
                'setting_reuse_strategy' => $evaluation->settings[SearchEvaluation::SETTING_REUSE_STRATEGY] ?? SearchEvaluation::REUSE_STRATEGY_NONE,
                'setting_auto_restart' => $evaluation->settings[SearchEvaluation::SETTING_AUTO_RESTART] ?? false,
            ] + $evaluation->toArray();

        if ($clone) {
            unset($values[SearchEvaluation::FIELD_ID]);
            unset($values[SearchEvaluation::FIELD_SCALE_TYPE]);

            $values[SearchEvaluation::FIELD_NAME] .= ' clone';
            $values[SearchEvaluation::FIELD_STATUS] = SearchEvaluation::STATUS_PENDING;

            foreach ($values['metrics'] as $key => $metric) {
                unset($values['metrics'][$key][EvaluationMetric::FIELD_ID]);
                unset($values['metrics'][$key][EvaluationMetric::FIELD_SEARCH_EVALUATION_ID]);
                unset($values['metrics'][$key][EvaluationMetric::FIELD_VALUE]);
            }

            $this->evaluation = null;
        }

        $this->fill($values);
    }
}
