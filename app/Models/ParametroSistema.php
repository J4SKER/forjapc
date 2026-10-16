<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Table('parametros_sistema')]
#[Fillable(['clave', 'valor', 'tipo', 'descripcion'])]
class ParametroSistema extends Model
{
    private const CACHE_KEY = 'parametros_sistema';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Devuelve el valor de un parámetro ya convertido a su tipo.
     */
    public static function valor(string $clave, mixed $porDefecto = null): mixed
    {
        $parametros = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->get(['clave', 'valor', 'tipo'])->keyBy('clave')->toArray(),
        );

        if (! isset($parametros[$clave])) {
            return $porDefecto;
        }

        $valor = $parametros[$clave]['valor'];

        return match ($parametros[$clave]['tipo']) {
            'entero' => (int) $valor,
            'decimal' => (float) $valor,
            'booleano' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            default => $valor,
        };
    }
}
