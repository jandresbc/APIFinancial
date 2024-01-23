<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class config_business extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'config_negocios';
}
