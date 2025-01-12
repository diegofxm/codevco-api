<?php

namespace App\Models\Catalogs;

use App\Models\Companies\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'code',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
