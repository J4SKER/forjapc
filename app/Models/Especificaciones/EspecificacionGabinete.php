<?php

namespace App\Models\Especificaciones;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('especificaciones_gabinete')]
class EspecificacionGabinete extends Especificacion
{
    public function filas(): array
    {
        return [
            'Placa máxima' => $this->formato_max_placa,
            'Largo máximo de tarjeta de video' => "{$this->gpu_max_mm} mm",
            'Altura máxima de disipador' => "{$this->disipador_max_mm} mm",
            'Radiador máximo' => $this->radiador_max_mm ? "{$this->radiador_max_mm} mm" : 'No admite',
            'Fuente' => $this->formato_fuente,
            'Bahías 2.5" / 3.5"' => "{$this->bahias_25} / {$this->bahias_35}",
        ];
    }

    public function resumen(): string
    {
        return "Hasta {$this->formato_max_placa} · GPU {$this->gpu_max_mm} mm";
    }
}
