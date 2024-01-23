<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelfManagerLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';
    protected $table = 'selfmanager_log';
}
