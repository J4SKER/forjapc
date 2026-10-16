<?php

namespace App\Models\Especificaciones;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base de las tablas de especificaciones técnicas: una fila por producto, con el
 * producto_id como llave primaria.
 */
abstract class Especificacion extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'producto_id';

    protected $guarded = ['producto_id'];

    /**
     * Especificaciones listas para mostrar en la ficha del producto.
     *
     * @return array<string, string>
     */
    abstract public function filas(): array;

    /**
     * Texto corto para las tarjetas del catálogo.
     */
    abstract public function resumen(): string;

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    protected static function siNo(bool $valor): string
    {
        return $valor ? 'Sí' : 'No';
    }
}
