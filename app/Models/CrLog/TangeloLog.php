<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TangeloLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';
    protected $table = 'tangelo_logs';

    
}
