<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandContactFlowLog extends Model
{
    use HasFactory;
    protected $connection = 'logs';

    protected $table = "command_contact_flow_logs";

    protected $fillable = [
        'type',
        'message',
        'code',
        'created_at',
        'updated_at'
    ];

    public $timestamps = false;
}
