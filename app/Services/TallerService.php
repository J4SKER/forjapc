<?php

namespace App\Services;

use App\Enums\EstadoOrdenArmado;
use App\Enums\EstadoPedido;
use App\Models\OrdenArmado;
use App\Models\Pedido;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Taller de armado: una orden por cada configuración pagada que incluye el servicio.
 */
class TallerService
{
    /**
     * Genera las órdenes de armado del pedido y devuelve cuántas se crearon.
     */
    public function crearOrdenes(Pedido $pedido): int
    {
        $grupos = $pedido->items()->where('con_armado', true)->whereNotNull('grupo_configuracion')
            ->select('grupo_configuracion', 'nombre_configuracion')->distinct()->get();

        foreach ($grupos as $grupo) {
            $orden = new OrdenArmado;
            $orden->forceFill([
                'pedido_id' => $pedido->id,
                'grupo_configuracion' => $grupo->grupo_configuracion,
                'nombre_configuracion' => $grupo->nombre_configuracion,
                'estado' => EstadoOrdenArmado::Pendiente,
                'checklist' => array_fill_keys(array_keys(OrdenArmado::CHECKLIST), false),
            ])->save();
        }

        return $grupos->count();
    }

    public function tomar(OrdenArmado $orden, User $tecnico): void
    {
        if ($orden->estado !== EstadoOrdenArmado::Pendiente && $orden->estado !== EstadoOrdenArmado::Incidencia) {
            throw new DomainException('La orden ya está en proceso o terminada.');
        }

        $orden->forceFill([
            'estado' => EstadoOrdenArmado::EnArmado,
            'tecnico_id' => $tecnico->id,
            'iniciada_at' => $orden->iniciada_at ?? now(),
        ])->save();
    }

    /**
     * @param  array<string, bool>  $checklist
     */
    public function actualizarChecklist(OrdenArmado $orden, array $checklist, ?string $notas): void
    {
        $this->asegurarEnProceso($orden);

        $orden->forceFill([
            'checklist' => collect(OrdenArmado::CHECKLIST)->mapWithKeys(fn ($t, $paso) => [$paso => (bool) ($checklist[$paso] ?? false)])->all(),
            'notas' => $notas,
            'estado' => EstadoOrdenArmado::EnArmado,
        ])->save();
    }

    /**
     * @param  array{temp_cpu_max: int, temp_gpu_max?: int|null, minutos_estres: int, aprobada: bool, observaciones?: string|null}  $pruebas
     */
    public function registrarPruebas(OrdenArmado $orden, array $pruebas): void
    {
        $this->asegurarEnProceso($orden);

        if (! $orden->checklistCompleto()) {
            throw new DomainException('Completa la lista de verificación antes de registrar las pruebas.');
        }

        $orden->forceFill(['pruebas' => $pruebas + ['registradas_at' => now()->toDateTimeString()], 'estado' => EstadoOrdenArmado::EnPruebas])->save();
    }

    /**
     * Termina la orden; cuando todas las órdenes del pedido están completas, el pedido queda listo para entrega.
     */
    public function completar(OrdenArmado $orden): void
    {
        if ($orden->estado !== EstadoOrdenArmado::EnPruebas || ! ($orden->pruebas['aprobada'] ?? false)) {
            throw new DomainException('Solo se completa una orden con pruebas de estrés aprobadas.');
        }

        DB::transaction(function () use ($orden) {
            $orden->forceFill(['estado' => EstadoOrdenArmado::Completada, 'completada_at' => now()])->save();

            $pedido = $orden->pedido;
            $pendientes = $pedido->ordenesArmado()->where('estado', '!=', EstadoOrdenArmado::Completada)->exists();

            if (! $pendientes && $pedido->estado === EstadoPedido::EnArmado) {
                app(PedidoService::class)->cambiarEstado($pedido, EstadoPedido::ListoEntrega, 'Armado y pruebas completados.');
            }
        });
    }

    public function reportarIncidencia(OrdenArmado $orden, string $descripcion): void
    {
        $orden->forceFill([
            'estado' => EstadoOrdenArmado::Incidencia,
            'notas' => trim(($orden->notas ? $orden->notas."\n" : '').'Incidencia '.now()->format('d/m/Y H:i').': '.$descripcion),
        ])->save();
    }

    private function asegurarEnProceso(OrdenArmado $orden): void
    {
        if (! in_array($orden->estado, [EstadoOrdenArmado::EnArmado, EstadoOrdenArmado::EnPruebas], true)) {
            throw new DomainException('Primero toma la orden para trabajar en ella.');
        }
    }
}
