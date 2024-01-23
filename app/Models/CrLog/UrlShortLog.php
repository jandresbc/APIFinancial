<?php

namespace App\Models\CrLog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlShortLog extends Model
{
    use HasFactory;

    protected $connection = 'logs';

    protected $table = "url_short_log";

    protected $fillable = [
        'type',
        'message',
        'new_url',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;
}
