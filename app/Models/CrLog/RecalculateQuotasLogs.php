<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecalculateQuotasLogs extends Model
{
    use HasFactory;

    protected $connection = 'logs';

    protected $table = "recalculate_quotas_logs";

    protected $fillable = [
        'type',
        'message',
        'code',
        'created_at',
        'updated_at'
    ];
}
