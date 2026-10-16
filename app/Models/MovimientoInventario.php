<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Kardex: cada entrada, salida o ajuste de inventario. Es inmutable; una corrección
 * se hace con un movimiento compensatorio.
 */
#[Table('movimientos_inventario')]
#[Fillable([
    'producto_id', 'tipo', 'cantidad', 'existencia_anterior', 'existencia_nueva', 'costo_unitario',
    'costo_promedio_anterior', 'costo_promedio_nuevo', 'referencia_tipo', 'referencia_id', 'motivo', 'usuario_id',
])]
class MovimientoInventario extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'costo_unitario' => 'decimal:4',
            'costo_promedio_anterior' => 'decimal:4',
            'costo_promedio_nuevo' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los movimientos del kardex no se pueden modificar.'));
        static::deleting(fn () => throw new LogicException('Los movimientos del kardex no se pueden eliminar.'));
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }
}
