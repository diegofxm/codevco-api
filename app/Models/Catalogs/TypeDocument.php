<?php

namespace App\Models\Catalogs;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

class TypeDocument extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
