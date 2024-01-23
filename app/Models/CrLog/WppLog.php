<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WppLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';
    protected $table = 'wpp_log';

    protected $fillable = [
        'data',
        'from',
        'to',
        'response'
    ];
}
