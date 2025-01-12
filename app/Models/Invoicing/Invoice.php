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
        'prefix',
        'number',
        'cufe',
        'issue_date',
        'payment_due_date',
        'notes',
        'payment_exchange_rate',
        'total_discount',
        'total_tax',
        'subtotal',
        'total_amount',
        'status'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'payment_due_date' => 'date',
        'payment_exchange_rate' => 'decimal:2',
        'total_discount' => 'decimal:2',
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
}