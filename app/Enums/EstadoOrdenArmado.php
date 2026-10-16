<?php

namespace App\Enums;

enum EstadoOrdenArmado: string
{
    case Pendiente = 'PENDIENTE';
    case EnArmado = 'EN_ARMADO';
    case EnPruebas = 'EN_PRUEBAS';
    case Completada = 'COMPLETADA';
    case Incidencia = 'INCIDENCIA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnArmado => 'En armado',
            self::EnPruebas => 'En pruebas',
            self::Completada => 'Completada',
            self::Incidencia => 'Con incidencia',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'bg-slate-100 text-slate-700',
            self::EnArmado, self::EnPruebas => 'bg-marca-50 text-marca-700',
            self::Completada => 'bg-emerald-50 text-emerald-700',
            self::Incidencia => 'bg-red-50 text-red-700',
        };
    }
}
