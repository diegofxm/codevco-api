<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Catalogs\TypeOrganization;
use App\Models\Catalogs\TypeDocument;
use App\Models\Catalogs\TypeRegime;
use App\Models\Catalogs\TypeLiability;
use App\Models\Catalogs\City;
use App\Models\Companies\Company;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type_organization_id',
        'type_document_id',
        'document_number',
        'dv',
        'business_name',
        'trade_name',
        'type_regime_id',
        'type_liabilities',
        'economic_activities',
        'merchant_registration',
        'address',
        'location_id',
        'postal_code',
        'phone',
        'email',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'dv' => 'integer',
        'type_liabilities' => 'json',
        'economic_activities' => 'json'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function typeOrganization()
    {
        return $this->belongsTo(TypeOrganization::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function typeRegime()
    {
        return $this->belongsTo(TypeRegime::class);
    }

    public function location()
    {
        return $this->belongsTo(City::class, 'location_id', 'id');
    }
}
