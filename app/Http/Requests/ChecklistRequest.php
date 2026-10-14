<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Create/edit a checklist template. Authorization: route middleware (auditor) + ChecklistTemplatePolicy for edits. */
class ChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('auditor');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('checklist_templates', 'name')->ignore($this->route('template'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'questions' => ['required', 'string', 'max:20000'],
        ];
    }

    /** One question per line; blank lines ignored. */
    public function questions(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->input('questions')))));
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $q = $this->questions();
            if (count($q) < 1 || count($q) > 100 || collect($q)->contains(fn ($x) => mb_strlen($x) > 255)) {
                $v->errors()->add('questions', 'Enter 1–100 questions, one per line, each under 255 characters.');
            }
        }];
    }
}
