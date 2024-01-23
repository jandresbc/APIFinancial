<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Seller extends Model
{
    use Notifiable, SoftDeletes;

    protected $table = 'siigo_sellers';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';
}