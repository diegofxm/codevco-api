<?php

namespace App\Models\Invoicing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitNoteItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'debit_note_id',
        'product_id',
        'unit_measure_id',
        'tax_id',
        'quantity',
        'unit_price',
        'discount',
        'tax_amount',
        'subtotal',
        'total'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unitMeasure()
    {
        return $this->belongsTo(UnitMeasure::class);
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
}
