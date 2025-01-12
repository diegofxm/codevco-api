<?php

namespace App\Models\Catalogs;

use Illuminate\Database\Eloquent\Model;

class TypeOrganization extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];
}
