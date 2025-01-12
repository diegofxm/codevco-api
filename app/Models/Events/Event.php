<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Events\TypeEvent;
use App\Models\Companies\Company;
use App\Models\Invoicing\Invoice;
use App\Models\Invoicing\CreditNote;
use App\Models\Invoicing\DebitNote;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type_event_id',
        'company_id',
        'invoice_id',
        'credit_note_id',
        'debit_note_id',
        'description',
        'notes',
        'payload',
        'event_date',
        'status'
    ];

    protected $casts = [
        'payload' => 'json',
        'event_date' => 'datetime'
    ];

    // Relaciones
    public function typeEvent()
    {
        return $this->belongsTo(TypeEvent::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
    }
}
