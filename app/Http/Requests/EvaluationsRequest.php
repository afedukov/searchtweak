<?php

namespace App\Http\Requests;

use App\Models\SearchEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluationsRequest extends FormRequest
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
        return [
            'status' => ['nullable', 'string', Rule::in(array_map('strtolower', SearchEvaluation::STATUS_LABELS))],
            'scale_type' => ['nullable', 'string', Rule::in(SearchEvaluation::SCALE_TYPES)],
            'model_id' => ['nullable', 'integer'],
        ];
    }
}
