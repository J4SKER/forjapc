<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * Reglas de contraseña: más estrictas para el personal, que tiene acceso al panel.
 * En producción además se rechazan las contraseñas que aparecen en filtraciones conocidas
 * (consulta anónima a Have I Been Pwned: solo se envían los 5 primeros caracteres del hash).
 */
class ReglasPassword
{
    public static function cliente(): Password
    {
        return self::conFiltraciones(Password::min(8));
    }

    public static function personal(): Password
    {
        return self::conFiltraciones(Password::min(10)->mixedCase()->numbers());
    }

    public static function para(?User $usuario): Password
    {
        return $usuario?->esPersonal() ? self::personal() : self::cliente();
    }

    private static function conFiltraciones(Password $regla): Password
    {
        return app()->isProduction() ? $regla->uncompromised() : $regla;
    }
}
