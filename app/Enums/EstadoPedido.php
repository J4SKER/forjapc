<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case PendientePago = 'PENDIENTE_PAGO';
    case Pagado = 'PAGADO';
    case EnArmado = 'EN_ARMADO';
    case EnPreparacion = 'EN_PREPARACION';
    case ListoEntrega = 'LISTO_ENTREGA';
    case Enviado = 'ENVIADO';
    case Entregado = 'ENTREGADO';
    case Cancelado = 'CANCELADO';
    case Reembolsado = 'REEMBOLSADO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendientePago => 'Pendiente de pago',
            self::Pagado => 'Pagado',
            self::EnArmado => 'En armado',
            self::EnPreparacion => 'En preparación',
            self::ListoEntrega => 'Listo para entrega',
            self::Enviado => 'Enviado',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
            self::Reembolsado => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendientePago => 'bg-amber-50 text-amber-800',
            self::Pagado, self::EnArmado, self::EnPreparacion => 'bg-marca-50 text-marca-700',
            self::ListoEntrega, self::Enviado => 'bg-sky-50 text-sky-800',
            self::Entregado => 'bg-emerald-50 text-emerald-700',
            self::Cancelado, self::Reembolsado => 'bg-slate-100 text-slate-600',
        };
    }

    /**
     * El inventario de este pedido sigue apartado (reservado) y no ha salido de bodega.
     */
    public function tieneReserva(): bool
    {
        return $this === self::PendientePago;
    }

    public function esFinal(): bool
    {
        return in_array($this, [self::Entregado, self::Cancelado, self::Reembolsado], true);
    }

    /**
     * Estados posteriores al pago en los que la mercadería aún no sale de la tienda.
     */
    public function estaEnTienda(): bool
    {
        return in_array($this, [self::Pagado, self::EnArmado, self::EnPreparacion, self::ListoEntrega], true);
    }
}
