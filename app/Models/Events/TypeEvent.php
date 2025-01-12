<?php

namespace App\Models\Events;

use Illuminate\Database\Eloquent\Model;
use App\Models\Events\Event;

class TypeEvent extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    // Relaciones
    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
