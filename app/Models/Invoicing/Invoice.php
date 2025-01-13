<?php

namespace App\Models\Invoicing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Companies\Company;
use App\Models\Customers\Customer;
use App\Models\Invoicing\Resolution;
use App\Models\Companies\Branch;
use App\Models\Catalogs\Currency;
use App\Models\Catalogs\PaymentMethod;
use App\Models\Catalogs\TypeOperation;
use App\Models\Invoicing\InvoiceLine;
use App\Models\Invoicing\InvoiceItem;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'resolution_id',
        'branch_id',
        'currency_id',
        'payment_method_id',
        'type_operation_id',
        'number',
        'prefix',
        'cufe',
        'issue_date',
        'payment_due_date',
        'notes',
        'payment_exchange_rate',
        'total_discount',
        'total_charges',
        'total_tax',
        'subtotal',
        'total_amount',
        'status',
        'order_reference',
        'delivery_terms',
        'delivery_date'
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'payment_due_date' => 'datetime',
        'delivery_date' => 'datetime',
        'payment_exchange_rate' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    // Relaciones
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function resolution()
    {
        return $this->belongsTo(Resolution::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function typeOperation()
    {
        return $this->belongsTo(TypeOperation::class);
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceLine::class);
    }
}