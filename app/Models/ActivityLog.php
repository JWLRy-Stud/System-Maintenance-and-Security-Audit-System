<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only: no update/delete routes exist, and model events block them. */
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'outcome', 'request_id'];

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
