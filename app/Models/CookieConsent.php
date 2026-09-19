<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookieConsent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime', 'preferences' => 'array'];
    }
}
