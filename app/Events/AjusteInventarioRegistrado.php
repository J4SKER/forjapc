<?php

namespace App\Events;

use App\Models\MovimientoInventario;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se dispara dentro de la transacción de un ajuste de inventario (merma o sobrante).
 */
class AjusteInventarioRegistrado
{
    use Dispatchable;

    public function __construct(public MovimientoInventario $movimiento) {}
}
