<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Pedido de un cliente. Los montos incluyen IVA; la base imponible y el IVA se guardan
 * separados. El estado solo cambia mediante PedidoService (transiciones válidas).
 */
class Pedido extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPedido::class,
            'metodo_pago' => MetodoPago::class,
            'direccion_envio' => 'array',
            'subtotal' => 'decimal:2',
            'costo_envio' => 'decimal:2',
            'costo_armado' => 'decimal:2',
            'total' => 'decimal:2',
            'base_imponible' => 'decimal:2',
            'iva' => 'decimal:2',
            'vence_at' => 'datetime',
            'pagado_at' => 'datetime',
            'entregado_at' => 'datetime',
            'cancelado_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'numero';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Departamento, $this>
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * @return HasMany<PedidoItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    /**
     * @return HasMany<PedidoHistorialEstado, $this>
     */
    public function historial(): HasMany
    {
        return $this->hasMany(PedidoHistorialEstado::class)->latest('id');
    }

    /**
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class)->latest('id');
    }

    /**
     * @return HasOne<Pago, $this>
     */
    public function pagoAprobado(): HasOne
    {
        return $this->hasOne(Pago::class)->where('estado', EstadoPago::Aprobado);
    }

    /**
     * @return HasMany<OrdenArmado, $this>
     */
    public function ordenesArmado(): HasMany
    {
        return $this->hasMany(OrdenArmado::class);
    }

    public function estaPendienteDePago(): bool
    {
        return $this->estado === EstadoPedido::PendientePago;
    }

    public function tienePagoPorVerificar(): bool
    {
        return $this->pagos()->where('estado', EstadoPago::Pendiente)->whereNotNull('comprobante_ruta')->exists();
    }

    public function esEnvio(): bool
    {
        return $this->metodo_entrega === 'ENVIO';
    }

    /**
     * Líneas agrupadas: productos sueltos y configuraciones como un solo bloque.
     *
     * @return Collection<string, Collection<int, PedidoItem>>
     */
    public function itemsAgrupados(): Collection
    {
        return $this->items->groupBy(fn (PedidoItem $item) => $item->grupo_configuracion ?? 'producto-'.$item->id);
    }
}
