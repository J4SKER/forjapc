<?php

namespace App\Enums;

enum TipoMovimiento: string
{
    case EntradaCompra = 'ENTRADA_COMPRA';
    case SalidaVenta = 'SALIDA_VENTA';
    case AjusteEntrada = 'AJUSTE_ENTRADA';
    case AjusteSalida = 'AJUSTE_SALIDA';
    case Devolucion = 'DEVOLUCION';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EntradaCompra => 'Entrada por compra',
            self::SalidaVenta => 'Salida por venta',
            self::AjusteEntrada => 'Ajuste (sobrante)',
            self::AjusteSalida => 'Ajuste (merma)',
            self::Devolucion => 'Devolución',
        };
    }

    public function esEntrada(): bool
    {
        return in_array($this, [self::EntradaCompra, self::AjusteEntrada, self::Devolucion], true);
    }
}
