<?php

namespace App\Models;

use App\Enums\EstadoCompra;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['proveedor_id', 'serie_factura', 'numero_factura', 'fecha_factura', 'notas'])]
class Compra extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'BORRADOR',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoCompra::class,
            'fecha_factura' => 'date',
            'recibida_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function esBorrador(): bool
    {
        return $this->estado === EstadoCompra::Borrador;
    }

    public function factura(): string
    {
        return trim(($this->serie_factura ? $this->serie_factura.'-' : '').$this->numero_factura);
    }

    /**
     * @return BelongsTo<Proveedor, $this>
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * @return HasMany<CompraDetalle, $this>
     */
    public function detalle(): HasMany
    {
        return $this->hasMany(CompraDetalle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibida_por')->withTrashed();
    }
}
