<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageWhatsappLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';
    protected $table = 'message_whatsapp_log';

    protected $fillable = [
        'id_identification',
        'validator',
        'whatsapp_id',
        'phone_client',
        'from',
        'to',
        'ack',
        'type',
        'body',
        'fromMe',
        'time',
        'response_client',
        'hash',
        'state',
        'created_at',
        'updated_at',
        'id_request'
    ];

    public $timestamps = false;
}
