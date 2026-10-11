<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService
{
    /** Never pass passwords, tokens or other secrets here: only action, target and outcome are stored. */
    public static function record(string $action, ?Model $subject = null, string $outcome = 'success', ?int $actorId = null): void
    {
        if (! app()->bound('request-id')) {
            app()->instance('request-id', (string) Str::uuid());
        }

        ActivityLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'outcome' => $outcome,
            'request_id' => app('request-id'),
        ]);
    }
}
