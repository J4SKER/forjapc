<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'tarifa_envio', 'dias_entrega', 'activo'])]
class Departamento extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tarifa_envio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Direccion, $this>
     */
    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }
}
