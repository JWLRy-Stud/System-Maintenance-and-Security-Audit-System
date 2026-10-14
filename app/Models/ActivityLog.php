<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only: no update/delete routes exist, and model events block them. */
class ActivityLog extends Model
{
    public $timestamps = false;

    // BUG B-30 [OPEN]: append-only is enforced by model events only; a mass query delete()/truncate or direct SQL bypasses it.
    //   FIX: in production run the app with a MySQL user that has only SELECT+INSERT on activity_logs (migrate with a separate
    //   user), and add a DB trigger or documented grant check; see docs/DEPLOYMENT.md.
    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'outcome', 'request_id', 'ip_address', 'detail'];

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
