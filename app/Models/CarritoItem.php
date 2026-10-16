<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Artículo del carrito: un producto suelto o una configuración completa (un solo artículo
 * con sus componentes y el servicio de armado opcional).
 */
#[Table('carrito_items')]
#[Fillable(['tipo', 'producto_id', 'cantidad', 'nombre', 'componentes', 'incluye_armado'])]
class CarritoItem extends Model
{
    public const PRODUCTO = 'PRODUCTO';

    public const CONFIGURACION = 'CONFIGURACION';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'componentes' => 'array',
            'incluye_armado' => 'boolean',
        ];
    }

    public function esConfiguracion(): bool
    {
        return $this->tipo === self::CONFIGURACION;
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * @return BelongsTo<Carrito, $this>
     */
    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class);
    }
}
