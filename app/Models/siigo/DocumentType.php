<?php

namespace App\Models\Siigo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use Notifiable, SoftDeletes;

    protected $table = 'siigo_documents_type';
    protected $guarded = [];
    public $timestamps = true;
    protected $primaryKey = 'Id';

}
