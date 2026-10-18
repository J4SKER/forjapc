<?php

namespace App\Exceptions;

use App\Models\Producto;
use DomainException;

class InventarioInsuficienteException extends DomainException
{
    public static function para(Producto $producto, int $solicitado, int $disponible): self
    {
        return new self("No hay suficiente existencia de «{$producto->nombre}»: se pidieron {$solicitado} y hay {$disponible} ".($disponible === 1 ? 'disponible.' : 'disponibles.'));
    }
}
