<?php

namespace App\Models\Especificaciones;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('especificaciones_ram')]
class EspecificacionRam extends Especificacion
{
    public function filas(): array
    {
        $porModulo = intdiv($this->capacidad_total_gb, max(1, $this->modulos));

        return [
            'Tipo' => $this->tipo,
            'Capacidad' => "{$this->capacidad_total_gb} GB ({$this->modulos} × {$porModulo} GB)",
            'Velocidad' => "{$this->velocidad_mhz} MHz",
            'Latencia' => $this->latencia_cl ? "CL{$this->latencia_cl}" : '—',
        ];
    }

    public function resumen(): string
    {
        return "{$this->tipo} · {$this->capacidad_total_gb} GB · {$this->velocidad_mhz} MHz";
    }
}
