<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('partida_detalle')]
class PartidaDetalle extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['debe' => 'decimal:2', 'haber' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<CuentaContable, $this>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }

    /**
     * @return BelongsTo<Partida, $this>
     */
    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }
}
