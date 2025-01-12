<?php

namespace App\Models\Catalogs;

use App\Models\Companies\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeLiability extends Model
{
    protected $table = 'type_liabilities';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_type_liabilities');
    }
}