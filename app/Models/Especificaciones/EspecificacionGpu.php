<?php

namespace App\Models\Especificaciones;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('especificaciones_gpu')]
class EspecificacionGpu extends Especificacion
{
    public function filas(): array
    {
        return [
            'Chipset' => $this->chipset,
            'Memoria de video' => "{$this->vram_gb} GB",
            'Longitud' => "{$this->longitud_mm} mm",
            'Consumo (TDP)' => "{$this->tdp_w} W",
            'Fuente recomendada' => "{$this->fuente_recomendada_w} W",
            'Conectores de energía' => $this->conectores_energia,
        ];
    }

    public function resumen(): string
    {
        return "{$this->chipset} · {$this->vram_gb} GB · {$this->longitud_mm} mm";
    }
}
