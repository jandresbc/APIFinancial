<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactFlowLog extends Model
{
    use HasFactory;


    protected $connection = 'logs';

    protected $table = "contacts_flows_log";

    protected $fillable = [
        'type',
        'message',
        'created_at',
        'updated_at'
    ];

    public $timestamps = false;
}
