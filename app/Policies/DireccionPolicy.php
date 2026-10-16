<?php

namespace App\Policies;

use App\Models\Direccion;
use App\Models\User;

/**
 * Cada cliente solo puede ver y modificar sus propias direcciones.
 */
class DireccionPolicy
{
    public function update(User $user, Direccion $direccion): bool
    {
        return $direccion->usuario_id === $user->id;
    }

    public function delete(User $user, Direccion $direccion): bool
    {
        return $direccion->usuario_id === $user->id;
    }
}
