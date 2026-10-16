<?php

namespace App\Models;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
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
            'metodo' => MetodoPago::class,
            'estado' => EstadoPago::class,
            'monto' => 'decimal:2',
            'monto_usd' => 'decimal:2',
            'tipo_cambio' => 'decimal:5',
            'comision' => 'decimal:2',
            'verificado_at' => 'datetime',
            'datos' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return HasMany<Reembolso, $this>
     */
    public function reembolsos(): HasMany
    {
        return $this->hasMany(Reembolso::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por')->withTrashed();
    }

    public function montoReembolsado(): float
    {
        return (float) $this->reembolsos()->sum('monto');
    }

    public function saldoReembolsable(): float
    {
        return round((float) $this->monto - $this->montoReembolsado(), 2);
    }
}
