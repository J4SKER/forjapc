<?php

namespace App\Enums;

enum EstadoResena: string
{
    case Pendiente = 'PENDIENTE';
    case Aprobada = 'APROBADA';
    case Rechazada = 'RECHAZADA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobada => 'Aprobada',
            self::Rechazada => 'Rechazada',
        };
    }
}
