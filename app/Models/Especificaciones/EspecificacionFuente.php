<?php

namespace App\Models\Especificaciones;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('especificaciones_fuente')]
class EspecificacionFuente extends Especificacion
{
    public function filas(): array
    {
        return [
            'Potencia' => "{$this->potencia_w} W",
            'Certificación' => $this->certificacion,
            'Modular' => $this->modular,
            'Formato' => $this->formato,
        ];
    }

    public function resumen(): string
    {
        return "{$this->potencia_w} W · {$this->certificacion}";
    }
}
