<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class TypeRegime extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];
}
