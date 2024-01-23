<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class prod_products extends Model
{
    protected $connection = 'mysql';

    protected $table = 'prod_productos';

    use HasFactory;
}
