<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = [
        'code',
        'name',
        'rate',
        'type',
        'description',
        'status'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'status' => 'boolean'
    ];
}
