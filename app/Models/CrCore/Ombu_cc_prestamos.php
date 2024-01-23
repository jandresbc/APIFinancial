<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ombu_cc_prestamos extends Model
{
    protected $connection = 'mysql';
    use HasFactory;
    protected $fillable = [
      'estado_solicitud'  
    ];
    public $timestamps = false;
}
