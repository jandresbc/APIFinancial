<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departamento extends Model
{
    use HasFactory;
    use Notifiable, SoftDeletes;

    protected $table = 'geo_departamentos';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';
}
