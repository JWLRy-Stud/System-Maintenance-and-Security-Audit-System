<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['checklist_template_id', 'question', 'position'];
}
