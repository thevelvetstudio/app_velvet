<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'invitation_expires_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function slot()
    {
        return $this->belongsTo(InterviewSlot::class, 'interview_slot_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
