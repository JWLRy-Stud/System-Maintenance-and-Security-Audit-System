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
            // BUG B-20 [OPEN]: an admin can set 'under_maintenance' by hand (the maintenance workflow manages that status)
            //   and can retire an asset that still has open maintenance requests. FIX: hide 'under_maintenance' in
            //   resources/views/assets/form.blade.php and refuse/confirm retiring while requests are open.
            'status' => ['required', Rule::in(Asset::STATUSES)],
            // BUG B-21 [OPEN]: accepts inactive users. FIX: Rule::exists('users','id')->where('status','active') (see activeUser() in SecurityAuditController).
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
