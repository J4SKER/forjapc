<?php

namespace App\Models;

use App\Enums\TipoComponente;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

#[Table('categorias')]
#[Fillable(['nombre', 'slug', 'icono', 'tipo_componente', 'orden', 'activo'])]
class Categoria extends Model
{
    public const CACHE_MENU = 'categorias_menu';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_componente' => TipoComponente::class,
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_MENU));
        static::deleted(fn () => Cache::forget(self::CACHE_MENU));
    }

    /**
     * Categorías activas para el menú de la tienda. Se guardan en caché como arreglos
     * simples porque la caché no deserializa objetos.
     *
     * @return Collection<int, array{id: int, nombre: string, slug: string, icono: string}>
     */
    public static function menu(): Collection
    {
        return collect(Cache::rememberForever(
            self::CACHE_MENU,
            fn () => static::query()->activas()->orderBy('orden')->get(['id', 'nombre', 'slug', 'icono'])->toArray(),
        ));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActivas(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * @return HasMany<Producto, $this>
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
