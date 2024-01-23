<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class PaymentType extends Model
{
    use Notifiable, SoftDeletes;

    protected $table = 'siigo_payment_types';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';
}
