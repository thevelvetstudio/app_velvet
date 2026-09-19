<?php

namespace App\Models;

use App\Enums\CandidateType;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $appends = ['full_name'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'status' => LeadStatus::class, 'candidate_type' => CandidateType::class];
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function candidate()
    {
        return $this->hasOne(Candidate::class);
    }

    public function documents()
    {
        return $this->hasMany(LeadDocument::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
