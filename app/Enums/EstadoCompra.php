<?php

namespace App\Enums;

enum EstadoCompra: string
{
    case Borrador = 'BORRADOR';
    case Recibida = 'RECIBIDA';
    case Anulada = 'ANULADA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Recibida => 'Recibida',
            self::Anulada => 'Anulada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'bg-amber-50 text-amber-700',
            self::Recibida => 'bg-emerald-50 text-emerald-700',
            self::Anulada => 'bg-slate-100 text-slate-500',
        };
    }
}
