<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingDraft extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'last_activity_at' => 'datetime'];
    }
}
