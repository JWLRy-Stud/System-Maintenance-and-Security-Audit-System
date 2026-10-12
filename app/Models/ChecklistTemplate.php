<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    protected $fillable = ['name', 'description', 'created_by'];

    public function items() { return $this->hasMany(ChecklistItem::class)->orderBy('position'); }
}
