<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('periodos_contables')]
class PeriodoContable extends Model
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
        return ['cerrado_at' => 'datetime'];
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'CERRADO';
    }

    public function nombre(): string
    {
        return ucfirst(now()->setDate($this->anio, $this->mes, 1)->translatedFormat('F Y'));
    }

    /**
     * @return HasMany<Partida, $this>
     */
    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class, 'periodo_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por')->withTrashed();
    }
}
