<?php

namespace App\Models\Invoicing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Companies\Branch;

class DebitNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'resolution_id',
        'branch_id',
        'number',
        'prefix',
        'cufe',
        'issue_date',
        'notes',
        'correction_concept',
        'discrepancy_code',
        'total_discount',
        'total_tax',
        'subtotal',
        'total_amount',
        'status'
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    // Relaciones
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function resolution()
    {
        return $this->belongsTo(Resolution::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
