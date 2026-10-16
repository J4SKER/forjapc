<?php

namespace App\Models\Especificaciones;

use App\Models\Socket;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('especificaciones_placa_base')]
class EspecificacionPlaca extends Especificacion
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['wifi' => 'boolean'];
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
            'Chipset' => $this->chipset,
            'Formato' => $this->formato,
            'Memoria' => "{$this->tipo_memoria} · {$this->ranuras_ram} ranuras · hasta {$this->ram_max_gb} GB",
            'Ranuras M.2' => (string) $this->slots_m2,
            'Puertos SATA' => (string) $this->puertos_sata,
            'Wi-Fi' => self::siNo($this->wifi),
        ];
    }

    public function resumen(): string
    {
        return "{$this->socket->nombre} · {$this->chipset} · {$this->formato} · {$this->tipo_memoria}";
    }
}
