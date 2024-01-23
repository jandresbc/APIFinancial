<?php

namespace App\Models\CrCore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticated;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class WsUsuarios extends Authenticated implements  JWTSubject
{
    protected $connection = 'mysql';
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'negocio_id',
        'username',
        'password',
        'estado'
    ];

    protected $hidden = [
        'password'
    ];

    public $timestamps = false;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
