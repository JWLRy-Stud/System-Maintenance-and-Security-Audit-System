<?php

namespace App\Policies;

use App\Models\ChecklistTemplate;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

class ChecklistTemplatePolicy
{
    use AdminBypass;

    /** Edit or delete: the auditor who created the template, or an admin (seeded templates have no creator, so admin only). */
    public function update(User $user, ChecklistTemplate $template): bool
    {
        return $user->isActive() && $user->role === 'auditor' && $template->created_by === $user->id;
    }

    public function delete(User $user, ChecklistTemplate $template): bool
    {
        return $this->update($user, $template);
    }
}
