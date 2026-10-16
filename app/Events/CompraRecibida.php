<?php

namespace App\Events;

use App\Models\Compra;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se dispara dentro de la transacción en la que una compra entra al inventario.
 */
class CompraRecibida
{
    use Dispatchable;

    public function __construct(public Compra $compra) {}
}
