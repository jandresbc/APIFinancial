<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parameters extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = "config_parametros";

    protected $fillable = [
        'grupo_id',
        'nombre',
        'valor',
        'type_person'
    ];
    
    public $timestamps = false;
}
