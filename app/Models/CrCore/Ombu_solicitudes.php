<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ombu_solicitudes extends Model
{
    protected $connection = 'mysql';
    use HasFactory;

    protected $fillable = ['data'];
    public $timestamps = false;
}
