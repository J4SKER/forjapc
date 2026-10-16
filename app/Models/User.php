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
}
