<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('technician', 'staff');
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', Rule::exists('assets', 'id')->where('status', '!=', 'retired')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
