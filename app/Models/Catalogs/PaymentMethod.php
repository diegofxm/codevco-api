<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];
}
