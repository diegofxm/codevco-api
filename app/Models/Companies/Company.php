<?php

namespace App\Models\Companies;

use App\Models\Catalogs\City;
use App\Models\Catalogs\EconomicActivity;
use App\Models\Catalogs\TypeDocument;
use App\Models\Catalogs\TypeLiability;
use App\Models\Catalogs\TypeOrganization;
use App\Models\Catalogs\TypeRegime;
use App\Models\Companies\Branch;
use App\Models\Invoicing\Resolution; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        'status',
        'subdomain'
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
            // Crear la sucursal principal con código basado en el ID de la compañía
            $branchCode = str_pad($company->id, 3, '0', STR_PAD_LEFT);
            Branch::create([
                'company_id' => $company->id,
                'code' => $branchCode,
                'name' => $company->business_name . ' - Principal',
                'address' => $company->address,
                'city_id' => $company->location_id,
                'phone' => $company->phone,
                'email' => $company->email,
                'manager_name' => 'Gerente Principal',
                'cost_center' => $branchCode,
                'is_main' => true,
                'status' => true
            ]);
        });

        static::updated(function ($company) {
            Log::info('Company updated event triggered', [
                'company_id' => $company->id,
                'changed_fields' => $company->getChanges(),
                'dirty_fields' => $company->getDirty()
            ]);

            // Buscar la sucursal principal
            $mainBranch = Branch::where('company_id', $company->id)
                              ->where('is_main', true)
                              ->first();
            
            Log::info('Main branch search result', [
                'found' => $mainBranch ? true : false,
                'branch_id' => $mainBranch ? $mainBranch->id : null
            ]);
            
            if ($mainBranch) {
                $updates = [];
                
                // Siempre actualizar estos campos si la compañía cambió
                if ($company->wasChanged()) {
                    $updates = [
                        'name' => $company->business_name . ' - Principal',
                        'address' => $company->address,
                        'city_id' => $company->location_id,
                        'phone' => $company->phone,
                        'email' => $company->email
                    ];
                }

                Log::info('Updates to be applied to main branch', [
                    'updates' => $updates,
                    'branch_id' => $mainBranch->id
                ]);

                if (!empty($updates)) {
                    try {
                        $mainBranch->update($updates);
                        Log::info('Main branch updated successfully', [
                            'branch_id' => $mainBranch->id,
                            'updates' => $updates
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error updating main branch', [
                            'error' => $e->getMessage(),
                            'branch_id' => $mainBranch->id,
                            'updates' => $updates
                        ]);
                    }
                }
            }
        });

        // Evento para eliminar archivos cuando se elimina la compañía
        static::deleting(function ($company) {
            // Eliminar logo si existe
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            
            // Eliminar certificado si existe
            if ($company->certificate_path) {
                Storage::disk('local')->delete($company->certificate_path);
            }

            // Limpiar los campos de archivos y contraseña
            $company->update([
                'logo_path' => null,
                'certificate_path' => null,
                'certificate_password' => null
            ]);
        });
    }

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
        return $this->belongsToMany(TypeLiability::class, 'company_type_liabilities');
    }

    public function economicActivities()
    {
        return $this->belongsToMany(EconomicActivity::class, 'company_economic_activities');
    }

    public function resolution()
    {
        return $this->hasOne(Resolution::class);
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
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
