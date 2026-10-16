<?php

namespace App\Enums;

enum Rol: string
{
    case Cliente = 'CLIENTE';
    case Vendedor = 'VENDEDOR';
    case Tecnico = 'TECNICO';
    case Contador = 'CONTADOR';
    case Admin = 'ADMIN';

    /**
     * Nombre legible del rol para mostrar en pantalla.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente',
            self::Vendedor => 'Ventas',
            self::Tecnico => 'Técnico de taller',
            self::Contador => 'Contabilidad',
            self::Admin => 'Administrador',
        };
    }

    /**
     * Indica si el rol pertenece al personal de ForjaPC (acceso al panel).
     */
    public function esPersonal(): bool
    {
        return $this !== self::Cliente;
    }

    /**
     * Roles que puede asignar el administrador a cuentas internas.
     *
     * @return list<self>
     */
    public static function internos(): array
    {
        return array_values(array_filter(self::cases(), fn (self $rol) => $rol->esPersonal()));
    }
}
