<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class SiigoDataLog extends Model
{
    use Notifiable, HasFactory;

    protected $table = 'siigo_data_logs';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $connection = 'logs';

}
