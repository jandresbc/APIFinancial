<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniBackUsuarios extends Model
{
    protected $connection = 'mysql';
    use HasFactory;

    public $timestamps = false;

    public function empleado()
    {
        return $this->hasOne(ConfigEmpleados::class,"id");
    }
}
