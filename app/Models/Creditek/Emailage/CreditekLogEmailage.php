<?php

namespace App\Models\Creditek\Emailage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditekLogEmailage extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'message',
        'type_log',
    ];

    public function data ()
    {
        return $this->hasOne(CreditekDataEmailage::class);
    }
}
