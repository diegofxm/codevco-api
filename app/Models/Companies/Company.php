<?php

namespace App\Models\Companies;

use App\Models\Catalogs\City;
use App\Models\Catalogs\EconomicActivity;
use App\Models\Catalogs\TypeDocument;
use App\Models\Catalogs\TypeLiability;
use App\Models\Catalogs\TypeOrganization;
use App\Models\Catalogs\TypeRegime;
use App\Models\Companies\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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
        'logo_path',
        'certificate_path',
        'certificate_password',
        'software_id',
        'software_pin',
        'test_set_id',
        'environment',
        'status'
    ];

    protected $hidden = [
        'certificate_password',
        'software_pin',
        'deleted_at'
    ];

    protected $casts = [
        'status' => 'boolean',
        'environment' => 'integer',
        'type_liabilities' => 'array',
        'economic_activities' => 'array'
    ];

    protected $with = [
        'typeOrganization:id,name,code',
        'typeDocument:id,name,code',
        'typeRegime:id,name,code',
        'typeLiabilities:id,name,code',
        'economicActivities:id,name,code',
        'location:id,name,code,department_id',
        'location.department:id,name,code,country_id',
        'location.department.country:id,name,code'
    ];

    protected static function booted()
    {
        static::created(function ($company) {
            // Crear la sucursal principal
            Branch::create([
                'company_id' => $company->id,
                'code' => '001',
                'name' => $company->business_name . ' - Principal',
                'address' => $company->address,
                'city_id' => $company->location_id,
                'phone' => $company->phone,
                'email' => $company->email,
                'manager_name' => 'Gerente Principal',
                'cost_center' => '001',
                'is_main' => true,
                'status' => true
            ]);
        });
    }

    // Relaciones
    public function branches()
    {
        return $this->hasMany(Branch::class);
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
        return $this->belongsTo(City::class, 'location_id');
    }

    public function typeLiabilities()
    {
        return $this->belongsToMany(TypeLiability::class, 'company_type_liabilities', 'company_id', 'type_liability_id');
    }

    public function economicActivities()
    {
        return $this->belongsToMany(EconomicActivity::class, 'company_economic_activities', 'company_id', 'economic_activity_id');
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getCertificateUrlAttribute()
    {
        return $this->certificate_path ? Storage::disk('private')->url($this->certificate_path) : null;
    }

    /**
     * Get the company identification number
     */
    public function getIdentificationNumber(): string
    {
        return $this->document_number;
    }

    /**
     * Get the company DV (verification digit)
     */
    public function getDv(): string
    {
        return $this->dv;
    }

    /**
     * Get the company business name
     */
    public function getBusinessName(): string
    {
        return $this->business_name;
    }

    /**
     * Get the company trade name
     */
    public function getTradeName(): string
    {
        return $this->trade_name ?? $this->business_name;
    }

    /**
     * Get the company address
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Get the company city code
     */
    public function getCityCode(): string
    {
        return $this->location->code;
    }

    /**
     * Get the company country code
     */
    public function getCountryCode(): string
    {
        return 'CO';
    }

    /**
     * Get the company tax responsibilities
     */
    public function getTaxResponsibilities(): array
    {
        return $this->typeLiabilities->pluck('code')->toArray();
    }

    /**
     * Get the company software ID
     */
    public function getSoftwareId(): string
    {
        return $this->software_id;
    }

    /**
     * Get the company software PIN
     */
    public function getSoftwarePin(): string
    {
        return $this->software_pin;
    }

    /**
     * Get the company certificate path
     */
    public function getCertificatePath(): string
    {
        return storage_path('app/certificates/' . $this->id . '/certificate.p12');
    }

    /**
     * Get the company certificate password
     */
    public function getCertificatePassword(): string
    {
        return $this->certificate_password;
    }

    /**
     * Get the company environment type (1: Production, 2: Testing)
     */
    public function getEnvironmentType(): int
    {
        return $this->environment;
    }
}
