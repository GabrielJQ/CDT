<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regiones';

    protected $fillable = [
        'clave',
        'nombre',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function unidadesOperativas(): HasMany
    {
        return $this->hasMany(UnidadOperativa::class);
    }
}
