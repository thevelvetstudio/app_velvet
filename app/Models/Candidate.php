<?php

namespace App\Models;

use App\Enums\CandidateStatus;
use App\Enums\CandidateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => CandidateStatus::class, 'candidate_type' => CandidateType::class, 'admitted_at' => 'datetime', 'activated_at' => 'datetime'];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities()
    {
        return $this->hasMany(CandidateActivity::class);
    }
}
