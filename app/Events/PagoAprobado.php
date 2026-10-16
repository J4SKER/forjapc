<?php

namespace App\Events;

use App\Models\Pago;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se dispara dentro de la transacción en la que un pago se aprueba y la mercadería sale del kardex.
 */
class PagoAprobado
{
    use Dispatchable;

    public function __construct(public Pago $pago) {}
}
