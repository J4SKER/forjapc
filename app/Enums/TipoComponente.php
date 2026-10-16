<?php

namespace App\Enums;

/**
 * Tipos de componente con especificaciones técnicas propias. Cada categoría del
 * catálogo puede estar asociada a uno de ellos (las de periféricos no tienen tipo).
 */
enum TipoComponente: string
{
    case Cpu = 'cpu';
    case Placa = 'placa';
    case Ram = 'ram';
    case Gpu = 'gpu';
    case Almacenamiento = 'almacenamiento';
    case Fuente = 'fuente';
    case Gabinete = 'gabinete';
    case Disipador = 'disipador';
    case Monitor = 'monitor';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Cpu => 'Procesador',
            self::Placa => 'Tarjeta madre',
            self::Ram => 'Memoria RAM',
            self::Gpu => 'Tarjeta de video',
            self::Almacenamiento => 'Almacenamiento',
            self::Fuente => 'Fuente de poder',
            self::Gabinete => 'Gabinete',
            self::Disipador => 'Disipador',
            self::Monitor => 'Monitor',
        };
    }

    /**
     * Nombre de la relación del modelo Producto que guarda sus especificaciones.
     */
    public function relacion(): string
    {
        return 'especificacion'.ucfirst($this->value);
    }
}
