<?php

namespace App\Models;

use App\Enums\EstadoOrdenArmado;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Orden de armado de una configuración pagada, con su lista de verificación y pruebas.
 */
#[Table('ordenes_armado')]
class OrdenArmado extends Model
{
    /**
     * Pasos de la lista de verificación del técnico.
     */
    public const CHECKLIST = [
        'cpu' => 'Instalación del procesador y pasta térmica',
        'disipador' => 'Montaje del disipador',
        'memoria' => 'Instalación de la memoria RAM',
        'almacenamiento' => 'Instalación del almacenamiento',
        'gpu' => 'Instalación de la tarjeta de video',
        'fuente' => 'Montaje y conexión de la fuente',
        'cables' => 'Gestión de cables',
        'bios' => 'Actualización de BIOS',
        'perfil_memoria' => 'Perfil de memoria (EXPO/XMP) activado',
    ];

    /**
     * @var list<string>
     */
    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoOrdenArmado::class,
            'checklist' => 'array',
            'pruebas' => 'array',
            'iniciada_at' => 'datetime',
            'completada_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Pedido, $this>
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id')->withTrashed();
    }

    /**
     * Piezas de la configuración que se arma.
     *
     * @return HasMany<PedidoItem, $this>
     */
    public function piezas(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'grupo_configuracion', 'grupo_configuracion')
            ->where('pedido_id', $this->pedido_id);
    }

    public function checklistCompleto(): bool
    {
        return collect(array_keys(self::CHECKLIST))->every(fn (string $paso) => (bool) ($this->checklist[$paso] ?? false));
    }
}
