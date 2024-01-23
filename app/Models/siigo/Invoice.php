<?php

namespace App\Models\Siigo;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use Notifiable, SoftDeletes;

    protected $table = 'siigo_invoices';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'id';
    protected $dates = ['due_date', 'ERP_doc_date'];

    public function billings(): HasMany
    {
        return $this->hasMany(Voucher::class, 'number_invoice', 'number');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'Identification', 'identification');
    }

}
