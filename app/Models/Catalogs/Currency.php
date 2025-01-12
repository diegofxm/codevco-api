<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];
}
