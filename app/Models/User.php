<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Table('usuarios')]
#[Fillable(['nombre', 'email', 'password', 'telefono', 'nit'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Clave de sesión donde se guarda el rol que el administrador está previsualizando.
     */
    public const SESION_VISTA_PREVIA = 'vista_previa_rol';

    /**
     * Valores por defecto, iguales a los de la base de datos, para que el modelo
     * recién creado los tenga sin volver a consultarlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rol' => 'CLIENTE',
        'nit' => 'CF',
        'activo' => true,
        'intentos_fallidos' => 0,
        'debe_cambiar_password' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
            'activo' => 'boolean',
            'debe_cambiar_password' => 'boolean',
            'bloqueado_hasta' => 'datetime',
            'ultimo_acceso' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Direccion, $this>
     */
    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class, 'usuario_id');
    }

    /**
     * @return HasMany<Configuracion, $this>
     */
    public function configuraciones(): HasMany
    {
        return $this->hasMany(Configuracion::class, 'usuario_id');
    }

    /**
     * @return HasMany<Pedido, $this>
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'usuario_id');
    }

    public function tieneRol(Rol ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function esPersonal(): bool
    {
        return $this->rol->esPersonal();
    }

    public function estaBloqueadoTemporalmente(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    /**
     * Rol con el que se muestra el panel: el administrador puede previsualizarlo
     * como otro rol del personal ("Ver como"); los demás usuarios usan el suyo.
     */
    public function rolEfectivo(): Rol
    {
        if ($this->rol !== Rol::Admin) {
            return $this->rol;
        }

        $rolPrevisualizado = Rol::tryFrom((string) session(self::SESION_VISTA_PREVIA));

        return $rolPrevisualizado?->esPersonal() ? $rolPrevisualizado : $this->rol;
    }

    public function estaEnVistaPrevia(): bool
    {
        return $this->rolEfectivo() !== $this->rol;
    }

    public function tieneRolEfectivo(Rol ...$roles): bool
    {
        return in_array($this->rolEfectivo(), $roles, true);
    }
}
