<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Companies\Company;
use App\Models\Catalogs\Tax;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'brand',
        'model',
        'customs_tariff',
        'price',
        'unit_measure_id',
        'tax_id',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function unitMeasure()
    {
        return $this->belongsTo(UnitMeasure::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    // Scope para buscar por código único dentro de una compañía
    public function scopeByCompanyAndCode($query, $companyId, $code)
    {
        return $query->where('company_id', $companyId)
                    ->where('code', $code);
    }
}
