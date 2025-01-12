<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class TypeOperation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];
}
