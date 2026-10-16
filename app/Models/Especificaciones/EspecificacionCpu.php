<?php

namespace App\Models\Especificaciones;

use App\Models\Socket;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('especificaciones_cpu')]
class EspecificacionCpu extends Especificacion
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['graficos_integrados' => 'boolean', 'incluye_disipador' => 'boolean'];
    }

    /**
     * @return BelongsTo<Socket, $this>
     */
    public function socket(): BelongsTo
    {
        return $this->belongsTo(Socket::class);
    }

    public function filas(): array
    {
        return [
            'Socket' => $this->socket->nombre,
            'Núcleos / hilos' => "{$this->nucleos} / {$this->hilos}",
            'Frecuencia' => "{$this->frecuencia_base_ghz} GHz".($this->frecuencia_turbo_ghz ? " (hasta {$this->frecuencia_turbo_ghz} GHz)" : ''),
            'TDP' => "{$this->tdp_w} W",
            'Memoria compatible' => $this->tipo_memoria,
            'Gráficos integrados' => self::siNo($this->graficos_integrados),
            'Incluye disipador' => self::siNo($this->incluye_disipador),
        ];
    }

    public function resumen(): string
    {
        return "{$this->nucleos} núcleos · {$this->socket->nombre} · {$this->tipo_memoria}";
    }
}
