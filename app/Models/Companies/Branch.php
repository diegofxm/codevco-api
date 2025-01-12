<?php

namespace App\Models\Companies;

use Illuminate\Database\Eloquent\Model;
use App\Models\Catalogs\City;
use App\Models\Invoicing\Resolution;
use App\Models\Invoicing\Invoice;
use App\Models\Invoicing\CreditNote;
use App\Models\Invoicing\DebitNote;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'city_id',
        'phone',
        'email',
        'manager_name',
        'cost_center',
        'is_main',
        'status'
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'status' => 'boolean'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function resolutions()
    {
        return $this->hasMany(Resolution::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class);
    }

    // Scopes
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
