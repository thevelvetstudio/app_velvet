<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractAppointment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function slot()
    {
        return $this->belongsTo(ContractSlot::class, 'contract_slot_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
