<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin'); // also enforced by route middleware
    }

    public function rules(): array
    {
        return [
            'asset_tag' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_tag')->ignore($this->route('asset'))],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
