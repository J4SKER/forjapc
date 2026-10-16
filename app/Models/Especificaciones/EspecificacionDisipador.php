<?php

namespace App\Models\Especificaciones;

use App\Models\Socket;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('especificaciones_disipador')]
class EspecificacionDisipador extends Especificacion
{
    /**
     * @return BelongsToMany<Socket, $this>
     */
    public function sockets(): BelongsToMany
    {
        return $this->belongsToMany(Socket::class, 'disipador_socket', 'producto_id', 'socket_id');
    }

    public function filas(): array
    {
        return [
            'Tipo' => $this->tipo,
            $this->tipo === 'Aire' ? 'Altura' : 'Radiador' => $this->tipo === 'Aire' ? "{$this->altura_mm} mm" : "{$this->radiador_mm} mm",
            'Disipa hasta' => "{$this->tdp_max_w} W",
            'Sockets compatibles' => $this->sockets->pluck('nombre')->join(', '),
        ];
    }

    public function resumen(): string
    {
        return $this->tipo === 'Aire'
            ? "Aire · {$this->altura_mm} mm · {$this->tdp_max_w} W"
            : "Líquida · {$this->radiador_mm} mm · {$this->tdp_max_w} W";
    }
}
