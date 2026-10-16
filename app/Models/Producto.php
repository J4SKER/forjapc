<?php

namespace App\Models;

use App\Enums\EstadoResena;
use App\Enums\TipoComponente;
use App\Models\Especificaciones\Especificacion;
use App\Models\Especificaciones\EspecificacionAlmacenamiento;
use App\Models\Especificaciones\EspecificacionCpu;
use App\Models\Especificaciones\EspecificacionDisipador;
use App\Models\Especificaciones\EspecificacionFuente;
use App\Models\Especificaciones\EspecificacionGabinete;
use App\Models\Especificaciones\EspecificacionGpu;
use App\Models\Especificaciones\EspecificacionMonitor;
use App\Models\Especificaciones\EspecificacionPlaca;
use App\Models\Especificaciones\EspecificacionRam;
use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use LogicException;

#[Fillable([
    'categoria_id', 'marca_id', 'sku', 'nombre', 'slug', 'descripcion', 'precio', 'precio_anterior',
    'garantia_meses', 'stock_minimo', 'consumo_watts', 'destacado', 'activo',
])]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Campos que solo el kardex (InventarioService) puede modificar.
     */
    public const CAMPOS_DE_INVENTARIO = ['existencia', 'reservado', 'costo_promedio'];

    private static bool $cambioDeInventarioAutorizado = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'precio_anterior' => 'decimal:2',
            'costo_promedio' => 'decimal:4',
            'existencia' => 'integer',
            'reservado' => 'integer',
            'destacado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $producto) {
            if (! self::$cambioDeInventarioAutorizado && $producto->isDirty(self::CAMPOS_DE_INVENTARIO)) {
                throw new LogicException('La existencia y el costo solo se modifican mediante movimientos del kardex.');
            }
        });
    }

    /**
     * Ejecuta un cambio de existencia, reserva o costo autorizado por el kardex.
     *
     * @template T
     *
     * @param  callable(): T  $cambio
     * @return T
     */
    public static function conCambioDeInventario(callable $cambio): mixed
    {
        self::$cambioDeInventarioAutorizado = true;

        try {
            return $cambio();
        } finally {
            self::$cambioDeInventarioAutorizado = false;
        }
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisibles(Builder $query): void
    {
        $query->where('activo', true)->whereHas('categoria', fn (Builder $q) => $q->where('activo', true));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeConDisponibilidad(Builder $query): void
    {
        $query->whereColumn('existencia', '>', 'reservado');
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return BelongsTo<Marca, $this>
     */
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /**
     * @return HasMany<ProductoImagen, $this>
     */
    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class)->orderBy('orden');
    }

    /**
     * @return HasMany<ResenaProducto, $this>
     */
    public function resenas(): HasMany
    {
        return $this->hasMany(ResenaProducto::class);
    }

    /**
     * @return HasMany<ResenaProducto, $this>
     */
    public function resenasAprobadas(): HasMany
    {
        return $this->resenas()->where('estado', EstadoResena::Aprobada);
    }

    /**
     * @return HasMany<MovimientoInventario, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * @return HasOne<EspecificacionCpu, $this>
     */
    public function especificacionCpu(): HasOne
    {
        return $this->hasOne(EspecificacionCpu::class);
    }

    /**
     * @return HasOne<EspecificacionPlaca, $this>
     */
    public function especificacionPlaca(): HasOne
    {
        return $this->hasOne(EspecificacionPlaca::class);
    }

    /**
     * @return HasOne<EspecificacionRam, $this>
     */
    public function especificacionRam(): HasOne
    {
        return $this->hasOne(EspecificacionRam::class);
    }

    /**
     * @return HasOne<EspecificacionGpu, $this>
     */
    public function especificacionGpu(): HasOne
    {
        return $this->hasOne(EspecificacionGpu::class);
    }

    /**
     * @return HasOne<EspecificacionAlmacenamiento, $this>
     */
    public function especificacionAlmacenamiento(): HasOne
    {
        return $this->hasOne(EspecificacionAlmacenamiento::class);
    }

    /**
     * @return HasOne<EspecificacionFuente, $this>
     */
    public function especificacionFuente(): HasOne
    {
        return $this->hasOne(EspecificacionFuente::class);
    }

    /**
     * @return HasOne<EspecificacionGabinete, $this>
     */
    public function especificacionGabinete(): HasOne
    {
        return $this->hasOne(EspecificacionGabinete::class);
    }

    /**
     * @return HasOne<EspecificacionDisipador, $this>
     */
    public function especificacionDisipador(): HasOne
    {
        return $this->hasOne(EspecificacionDisipador::class);
    }

    /**
     * @return HasOne<EspecificacionMonitor, $this>
     */
    public function especificacionMonitor(): HasOne
    {
        return $this->hasOne(EspecificacionMonitor::class);
    }

    public function tipoComponente(): ?TipoComponente
    {
        return $this->categoria?->tipo_componente;
    }

    /**
     * Especificación técnica según el tipo de componente de su categoría.
     */
    public function especificacion(): ?Especificacion
    {
        $tipo = $this->tipoComponente();

        return $tipo ? $this->{$tipo->relacion()} : null;
    }

    public function disponible(): int
    {
        return max(0, $this->existencia - $this->reservado);
    }

    public function estaDisponible(): bool
    {
        return $this->disponible() > 0;
    }

    public function stockBajo(): bool
    {
        return $this->disponible() <= $this->stock_minimo;
    }

    public function enOferta(): bool
    {
        return $this->precio_anterior !== null && (float) $this->precio_anterior > (float) $this->precio;
    }

    public function porcentajeDescuento(): int
    {
        return $this->enOferta() ? (int) round((1 - $this->precio / $this->precio_anterior) * 100) : 0;
    }

    public function imagenPrincipalUrl(): ?string
    {
        $imagen = $this->relationLoaded('imagenes') ? $this->imagenes->first() : $this->imagenes()->first();

        return $imagen ? Storage::disk('public')->url($imagen->ruta) : null;
    }

    public function calificacionPromedio(): ?float
    {
        $promedio = $this->resenasAprobadas()->avg('calificacion');

        return $promedio !== null ? round((float) $promedio, 1) : null;
    }
}
