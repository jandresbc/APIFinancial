<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Token extends Model
{
    use HasFactory;
    use Notifiable, SoftDeletes;

    protected $table = 'siigo_tokens';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';

    public function getIsExpiredAttribute()
    {
        $now = Carbon::now();
        return $now->gt($this->expires_in);
    }
}
