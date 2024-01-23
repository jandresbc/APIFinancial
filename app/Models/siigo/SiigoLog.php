<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiigoLog extends Model
{
    use Notifiable, SoftDeletes, HasFactory;

    protected $table = 'siigo_logs';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';

    protected $connection = 'logs';

    static function addNewRecord($customer, $loan, $type = null)
    {
        $firstName = isset($customer['first_name']) ?$customer['first_name'] : "";
        $lastName = isset($customer['last_name']) ?$customer['last_name'] : "";
        $log['identification'] = isset($customer['identification']) ? $customer['identification'] : null;
        $log['name'] = "{$firstName} {$lastName}";
        $log['email'] = isset($customer['email']) ? $customer['email'] : null;
        $log['loan'] = isset($loan['id']) ? $loan['id'] : null;
        $log['quota'] = isset($loan['quota']) ? $loan['quota'] : null;
        $log['type'] = isset($type) ? $type : null;
        return static::create($log);
    }

}
