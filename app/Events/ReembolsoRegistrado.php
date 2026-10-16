<?php

namespace App\Events;

use App\Models\Reembolso;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se dispara dentro de la transacción en la que se registra un reembolso.
 */
class ReembolsoRegistrado
{
    use Dispatchable;

    public function __construct(public Reembolso $reembolso) {}
}
