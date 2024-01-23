<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class contactFlows extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'contact_flows';

    protected $fillable = [
        'message',
        'plataform',
        'template',
        'day_execution',
        'hour',
        'created_at',
        'updated_at'
    ];
    public $timestamps = false;
}
