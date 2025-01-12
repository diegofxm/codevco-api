<?php

namespace App\Models\Invoicing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Companies\Company;
use App\Models\Companies\Branch;
use App\Models\Catalogs\TypeDocument;

class Resolution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'type_document_id',
        'prefix',
        'resolution',
        'resolution_date',
        'expiration_date',
        'technical_key',
        'from',
        'to',
        'current_number',
        'status'
    ];

    protected $casts = [
        'resolution_date' => 'date',
        'expiration_date' => 'date',
        'from' => 'integer',
        'to' => 'integer',
        'current_number' => 'integer',
        'status' => 'boolean'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
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
}
