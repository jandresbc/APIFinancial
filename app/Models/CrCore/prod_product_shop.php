<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class prod_product_shop extends Model
{
    protected $connection = 'mysql';

    protected $table = 'prod_productos_tienda';

    use HasFactory;

    public $timestamps = false;
}
