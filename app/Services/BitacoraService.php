<?php

namespace App\Services;

use App\Models\BitacoraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registra en la bitácora de auditoría las acciones sensibles del sistema.
 */
class BitacoraService
{
    public function __construct(private Request $request) {}

    /**
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $despues
     */
    public function registrar(string $accion, ?Model $entidad = null, ?array $antes = null, ?array $despues = null): BitacoraAuditoria
    {
        return BitacoraAuditoria::create([
            'usuario_id' => $this->request->user()?->id,
            'accion' => $accion,
            'entidad_tipo' => $entidad ? class_basename($entidad) : null,
            'entidad_id' => $entidad?->getKey(),
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'ip' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 255),
        ]);
    }
}
