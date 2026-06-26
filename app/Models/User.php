<?php

namespace App\Models;

use App\Enums\RolUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'region_id',
        'unidad_operativa_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'role' => RolUsuario::class,
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function unidadOperativa(): BelongsTo
    {
        return $this->belongsTo(UnidadOperativa::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === RolUsuario::Admin;
    }

    public function isNacional(): bool
    {
        return $this->role === RolUsuario::Nacional;
    }

    public function isRegional(): bool
    {
        return $this->role === RolUsuario::Regional;
    }

    public function isUnidad(): bool
    {
        return $this->role === RolUsuario::Unidad;
    }

    public function canManageUsers(): bool
    {
        return $this->role?->canManageUsers() ?? false;
    }

    public function canImportGlobal(): bool
    {
        return $this->role?->canImportGlobal() ?? false;
    }

    public function hasGlobalAccess(): bool
    {
        return $this->role?->isGlobal() ?? false;
    }
}
