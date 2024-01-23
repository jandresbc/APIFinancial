<?php

namespace App\Models\Creditek\Emailage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditekDataEmailage extends Model
{
    use HasFactory;

    protected $fillable = [
        'creditek_log_emailage_id',
        'consultation_date',
        'due_date',
        'data',
        'reason',
        'riskBand',
        'riskScore',
        'status',
    ];

    public function log()
    {
        return $this->blongsTo(CreditekLogEmailage::class);
    }
}
