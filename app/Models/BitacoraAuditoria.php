<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Registro inmutable de acciones sensibles: solo se inserta, nunca se modifica ni se borra.
 */
#[Table('bitacora_auditoria')]
#[Fillable(['usuario_id', 'accion', 'entidad_tipo', 'entidad_id', 'datos_anteriores', 'datos_nuevos', 'ip', 'user_agent'])]
class BitacoraAuditoria extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('La bitácora de auditoría no se puede modificar.'));
        static::deleting(fn () => throw new LogicException('La bitácora de auditoría no se puede eliminar.'));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }
}
