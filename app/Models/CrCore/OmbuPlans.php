<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OmbuPlans extends Model
{
    protected $connection = 'mysql';

    protected $table = 'ombu_planes';
    
    use HasFactory;
}
