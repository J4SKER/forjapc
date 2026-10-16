<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pedido_items')]
#[Fillable(['producto_id', 'descripcion', 'grupo_configuracion', 'nombre_configuracion', 'con_armado', 'cantidad', 'precio_unitario', 'subtotal'])]
class PedidoItem extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'con_armado' => 'boolean',
            'precio_unitario' => 'decimal:2',
            'costo_unitario' => 'decimal:4',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
