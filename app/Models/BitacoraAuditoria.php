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
     * Nombre de la acción para mostrar, con tildes.
     */
    public static function accionLegible(string $accion): string
    {
        $texto = strtr(str_replace('_', ' ', $accion), [
            'parametro' => 'parámetro', 'periodo' => 'período', 'resena' => 'reseña',
            'contrasena' => 'contraseña', 'password' => 'contraseña',
        ]);

        return mb_strtoupper(mb_substr($texto, 0, 1)).mb_substr($texto, 1);
    }

    /**
     * Nombre en español del tipo de registro afectado.
     */
    public function entidadLegible(): ?string
    {
        return match ($this->entidad_tipo) {
            null => null,
            'User' => 'Usuario',
            'ParametroSistema' => 'Parámetro',
            'PeriodoContable' => 'Período contable',
            'TipoCambio' => 'Tipo de cambio',
            'CuentaContable' => 'Cuenta contable',
            'ResenaProducto' => 'Reseña',
            'MovimientoInventario' => 'Movimiento de inventario',
            default => $this->entidad_tipo,
        };
    }

    /**
     * Valor de un cambio listo para mostrar.
     */
    public static function valorLegible(mixed $valor): string
    {
        return match (true) {
            $valor === null, $valor === '' => '(vacío)',
            is_bool($valor) => $valor ? 'Sí' : 'No',
            is_scalar($valor) => (string) $valor,
            default => json_encode($valor, JSON_UNESCAPED_UNICODE),
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }
}
