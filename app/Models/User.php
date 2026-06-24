<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
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
        return $this->role === 'admin';
    }

    public function isNacional(): bool
    {
        return $this->role === 'nacional';
    }

    public function isRegional(): bool
    {
        return $this->role === 'regional';
    }

    public function isUnidad(): bool
    {
        return $this->role === 'unidad';
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canImportGlobal(): bool
    {
        return $this->isAdmin() || $this->isNacional();
    }

    public function hasGlobalAccess(): bool
    {
        return $this->isAdmin() || $this->isNacional();
    }
}
