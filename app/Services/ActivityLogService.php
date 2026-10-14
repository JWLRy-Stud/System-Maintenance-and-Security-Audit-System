<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Never pass passwords, tokens or other secrets here. $detail is free text for the reader of the log (e.g. the
     * attempted login email); it is cut to 255 characters and only ever shown escaped.
     */
    public static function record(string $action, ?Model $subject = null, string $outcome = 'success', ?int $actorId = null, ?string $detail = null): void
    {
        $request = request();

        if ($outcome === 'denied') {
            $request->attributes->set('denial_logged', true); // so the central 403 logger does not add a second row
        }

        ActivityLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'outcome' => $outcome,
            'request_id' => self::requestId($request),
            'ip_address' => $request->ip(),
            'detail' => $detail === null ? null : Str::limit($detail, 255, ''),
        ]);
    }

    /** Called from the exception handler (bootstrap/app.php) for every 403 that was not already logged as 'denied'. */
    public static function recordForbidden(Request $request): void
    {
        if ($request->attributes->get('denial_logged')) {
            return;
        }

        try {
            self::record('access.denied', null, 'denied', null, $request->method().' '.($request->route()?->uri() ?? $request->path()));
        } catch (\Throwable $e) {
            report($e); // never turn a 403 into a 500 because the log could not be written
        }
    }

    /** One id per request. Stored on the Request object, so a long-running worker (Octane) never shares it between requests. */
    private static function requestId(Request $request): string
    {
        if (! $request->attributes->has('request_id')) {
            $request->attributes->set('request_id', (string) Str::uuid());
        }

        return $request->attributes->get('request_id');
    }
}
