<?php

namespace App\Models\Especificaciones;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('especificaciones_monitor')]
class EspecificacionMonitor extends Especificacion
{
    public function filas(): array
    {
        return [
            'Tamaño' => "{$this->tamano_pulgadas}\"",
            'Resolución' => $this->resolucion,
            'Frecuencia' => "{$this->frecuencia_hz} Hz",
            'Panel' => $this->panel,
            'Tiempo de respuesta' => "{$this->tiempo_respuesta_ms} ms",
        ];
    }

    public function resumen(): string
    {
        return "{$this->tamano_pulgadas}\" · {$this->resolucion} · {$this->frecuencia_hz} Hz";
    }
}
