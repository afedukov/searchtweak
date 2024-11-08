<?php

namespace App\Http\Requests;

use App\Models\SearchEvaluation;
use App\Models\SearchModel;
use App\Models\Tag;
use App\Models\Team;
use App\Rules\AutoRestartRule;
use App\Rules\EvaluationKeywordsRule;
use App\Rules\EvaluationMetricRule;
use App\Rules\EvaluationScaleRule;
use App\Rules\EvaluationTransformersRule;
use App\Rules\FeedbackStrategyRule;
use App\Rules\ReuseStrategyRule;
use App\Services\Scorers\Scales\BinaryScale;
use App\Services\Scorers\Scales\DetailScale;
use App\Services\Scorers\Scales\GradedScale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::getValidationRules() + [
            'keywords' => ['required', 'array', new EvaluationKeywordsRule()],
            'keywords.*' => ['nullable', 'string', 'max:255'],
            'transformers' => ['required', 'array', new EvaluationTransformersRule(true)],
        ];
    }

    public static function getValidationRules(): array
    {
        $teamId = self::getTeamId();

        return [
            'model_id' => ['required', Rule::exists('search_models', SearchModel::FIELD_ID)->where(SearchModel::FIELD_TEAM_ID, $teamId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'metrics' => ['required', 'array', new EvaluationMetricRule()],
            'setting_feedback_strategy' => ['required', 'integer', Rule::in([1, 3]), new FeedbackStrategyRule()],
            'setting_show_position' => ['required', 'boolean'],
            'setting_auto_restart' => ['required', 'boolean', new AutoRestartRule()],
            'setting_reuse_strategy' => ['required', 'integer', Rule::in([
                SearchEvaluation::REUSE_STRATEGY_NONE,
                SearchEvaluation::REUSE_STRATEGY_QUERY_DOC,
                SearchEvaluation::REUSE_STRATEGY_QUERY_DOC_POSITION,
            ]), new ReuseStrategyRule()],
            'tags' => ['nullable', 'array'],
            'tags.*.id' => ['integer', Rule::exists('tags', Tag::FIELD_ID)->where(Tag::FIELD_TEAM_ID, $teamId)],
            'scale_type' => ['required', 'string', Rule::in([BinaryScale::SCALE_TYPE, GradedScale::SCALE_TYPE, DetailScale::SCALE_TYPE]), new EvaluationScaleRule()],
        ];
    }

    public function messages(): array
    {
        return self::getValidationMessages();
    }

    public static function getValidationMessages(): array
    {
        return [
            'keywords.required' => 'At least one keyword is required.',
            'metrics.required' => 'At least one metric is required.',
            'tags' => 'The selected tags are invalid.',
            'tags.*.id' => 'The selected tag is invalid.',
        ];
    }

    private static function getTeamId(): int
    {
        $authenticated = Auth::user();

        return $authenticated instanceof Team ? $authenticated->id : $authenticated->current_team_id;
    }
}
