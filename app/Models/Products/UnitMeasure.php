<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;

class UnitMeasure extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description'
    ];

    // Relaciones
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
