<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlShort extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = "url_shorteners";

    protected $fillable = [
        'to_url',
        'url_key',
        'new_url',
        'created_at',
        'updated_at'
    ];
}
