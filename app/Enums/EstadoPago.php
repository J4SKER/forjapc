<?php

namespace App\Enums;

enum EstadoPago: string
{
    case Pendiente = 'PENDIENTE';
    case Aprobado = 'APROBADO';
    case Rechazado = 'RECHAZADO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Por verificar',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-amber-50 text-amber-800',
            self::Aprobado => 'bg-emerald-50 text-emerald-700',
            self::Rechazado => 'bg-red-50 text-red-700',
        };
    }
}
