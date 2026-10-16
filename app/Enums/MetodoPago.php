<?php

namespace App\Enums;

use App\Models\ParametroSistema;

enum MetodoPago: string
{
    case Paypal = 'PAYPAL';
    case Transferencia = 'TRANSFERENCIA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Paypal => 'PayPal o tarjeta',
            self::Transferencia => 'Transferencia o depósito',
        };
    }

    /**
     * Minutos que tiene el cliente para pagar antes de que el pedido venza.
     */
    public function minutosParaPagar(): int
    {
        return match ($this) {
            self::Paypal => (int) ParametroSistema::valor('minutos_vencimiento_pago_linea', 30),
            self::Transferencia => (int) ParametroSistema::valor('horas_vencimiento_transferencia', 48) * 60,
        };
    }
}
