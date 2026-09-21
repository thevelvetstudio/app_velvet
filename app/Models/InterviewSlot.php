<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSlot extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function interview()
    {
        return $this->hasOne(Interview::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
