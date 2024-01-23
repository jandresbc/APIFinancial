<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdProductos extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'prod_productos';

    public $timestamps = false;
}
