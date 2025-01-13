<?php

namespace App\Models\Companies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resolution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'number',
        'date',
        'due_date',
        'prefix',
        'from',
        'to',
        'current',
        'status'
    ];

    protected $dates = [
        'date',
        'due_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
