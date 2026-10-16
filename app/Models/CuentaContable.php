<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cuenta de la nomenclatura contable. Las de agrupación no aceptan movimientos.
 */
#[Table('cuentas_contables')]
#[Fillable(['codigo', 'nombre', 'tipo', 'naturaleza', 'padre_id', 'acepta_movimientos', 'activo'])]
class CuentaContable extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['acepta_movimientos' => 'boolean', 'activo' => 'boolean'];
    }

    /**
     * @return BelongsTo<CuentaContable, $this>
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    /**
     * @return HasMany<PartidaDetalle, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(PartidaDetalle::class, 'cuenta_id');
    }

    public function esDeudora(): bool
    {
        return $this->naturaleza === 'DEUDORA';
    }
}
