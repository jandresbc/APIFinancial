<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use Notifiable, SoftDeletes;

    protected $table = 'siigo_cities';

    protected $primaryKey = 'id';

    // public function account()
    // {
    //     return $this->belongsTo(Account::class,['StateCode', 'CityCode'], ['StateCode', 'CityCode']);
    // }
}
