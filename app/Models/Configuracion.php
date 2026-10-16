<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Configuración de PC guardada por un cliente. Se comparte mediante su código.
 */
#[Table('configuraciones')]
#[Fillable(['nombre', 'incluye_armado'])]
class Configuracion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['incluye_armado' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $configuracion) {
            do {
                $codigo = Str::lower(Str::random(10));
            } while (static::query()->where('codigo', $codigo)->exists());

            $configuracion->codigo ??= $codigo;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return HasMany<ConfiguracionComponente, $this>
     */
    public function componentes(): HasMany
    {
        return $this->hasMany(ConfiguracionComponente::class);
    }

    /**
     * Selección en el formato del configurador: tipo => [producto_id => cantidad].
     *
     * @return array<string, array<int, int>>
     */
    public function seleccion(): array
    {
        $seleccion = [];
        foreach ($this->componentes as $componente) {
            $seleccion[$componente->tipo][$componente->producto_id] = $componente->cantidad;
        }

        return $seleccion;
    }
}
