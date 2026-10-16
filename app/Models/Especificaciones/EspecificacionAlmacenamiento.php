<?php

namespace App\Models\Especificaciones;

use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('especificaciones_almacenamiento')]
class EspecificacionAlmacenamiento extends Especificacion
{
    public function capacidadLegible(): string
    {
        return $this->capacidad_gb >= 1000
            ? rtrim(rtrim(number_format($this->capacidad_gb / 1000, 1), '0'), '.').' TB'
            : "{$this->capacidad_gb} GB";
    }

    public function filas(): array
    {
        return [
            'Tipo' => $this->tipo,
            'Formato' => $this->formato,
            'Capacidad' => $this->capacidadLegible(),
            'Lectura secuencial' => $this->lectura_mbps ? "{$this->lectura_mbps} MB/s" : '—',
            'Escritura secuencial' => $this->escritura_mbps ? "{$this->escritura_mbps} MB/s" : '—',
        ];
    }

    public function resumen(): string
    {
        return "{$this->tipo} · {$this->capacidadLegible()}";
    }
}
