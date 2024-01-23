<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReminderLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';

    protected $table = "payment_reminder_log";

    protected $fillable = [
        'phone',
        'email',
        'message',
        'channel',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;
}
