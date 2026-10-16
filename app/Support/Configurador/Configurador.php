<?php

namespace App\Support\Configurador;

use App\Enums\TipoComponente;

/**
 * Pasos del configurador y componentes obligatorios.
 */
final class Configurador
{
    /**
     * Orden en que se eligen los componentes.
     */
    public const PASOS = [
        TipoComponente::Cpu,
        TipoComponente::Placa,
        TipoComponente::Ram,
        TipoComponente::Almacenamiento,
        TipoComponente::Gpu,
        TipoComponente::Gabinete,
        TipoComponente::Disipador,
        TipoComponente::Fuente,
    ];

    /**
     * Componentes sin los cuales la PC no se puede armar.
     */
    public const OBLIGATORIOS = [
        TipoComponente::Cpu,
        TipoComponente::Placa,
        TipoComponente::Ram,
        TipoComponente::Almacenamiento,
        TipoComponente::Gabinete,
        TipoComponente::Fuente,
    ];

    /**
     * Máximo de unidades por tipo (kits de RAM o unidades de almacenamiento).
     */
    public static function maximo(TipoComponente $tipo): int
    {
        return match ($tipo) {
            TipoComponente::Ram => 4,
            TipoComponente::Almacenamiento => 4,
            default => 1,
        };
    }
}
