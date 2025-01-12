<?php

namespace App\Models\Invoicing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Invoicing\Invoice;
use App\Models\Products\Product;
use App\Models\Products\UnitMeasure;
use App\Models\Catalogs\Tax;

class InvoiceLine extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_lines';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'price',
        'discount_rate',
        'discount_amount',
        'unit_measure_id',
        'tax_id',
        'tax_amount',
        'subtotal',
        'total',
        'period_start_date',
        'period_end_date'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'period_start_date' => 'date',
        'period_end_date' => 'date'
    ];

    // Relaciones
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
