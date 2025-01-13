<?php

namespace App\Models\Users;

use App\Models\Catalogs\TypeDocument;
use App\Models\Roles\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id',
        'type_document_id',
        'document_number',
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class);
    }
}