<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OmbuPersonas extends Model
{
    protected $connection = 'mysql';

    protected $table = 'ombu_personas';

    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'sexo',
        'tipo_doc',
        'nro_doc',
        'email'
    ];
    public $timestamps = false;
}
