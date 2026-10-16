<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Partida contable por partida doble. Es inmutable: se anula con una partida de reversión.
 */
#[Table('partidas')]
class Partida extends Model
{
    public const UPDATED_AT = null;

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
            'fecha' => 'date',
            'total_debe' => 'decimal:2',
            'total_haber' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Las partidas contables no se modifican.'));
        static::deleting(fn () => throw new LogicException('Las partidas contables no se borran.'));
    }

    /**
     * @return HasMany<PartidaDetalle, $this>
     */
    public function detalle(): HasMany
    {
        return $this->hasMany(PartidaDetalle::class)->orderByDesc('debe')->orderBy('id');
    }

    /**
     * @return BelongsTo<PeriodoContable, $this>
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoContable::class, 'periodo_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Partida, $this>
     */
    public function revierte(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revierte_partida_id');
    }

    /**
     * @return HasOne<Partida, $this>
     */
    public function reversion(): HasOne
    {
        return $this->hasOne(self::class, 'revierte_partida_id');
    }

    public function referencia(): string
    {
        return $this->periodo->anio.'-'.str_pad((string) $this->periodo->mes, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $this->numero, 4, '0', STR_PAD_LEFT);
    }
}
