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
        // BUG B-16 [OPEN]: app()->instance() keeps the request id for the life of the PHP process. Fine under php-fpm,
        //   but with Octane/queue workers different requests would share one id. FIX: bind it per request in a middleware.
        // BUG B-16 [OPEN]: other 403s (users, checklists, assigning findings) are not logged as 'denied'; only maintenance
        //   and finding transitions are. FIX: log denials centrally (Gate::after or the exception handler).
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
