<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Única vía para crear el primer administrador y para designar al propietario: requiere acceso
 * al servidor (SSH), así que ninguna página web puede otorgar estos permisos.
 */
#[Signature('forjapc:crear-admin {email : Correo del administrador} {--nombre=Administrador ForjaPC : Nombre completo} {--propietario : Lo designa propietario (puede nombrar y modificar administradores)}')]
#[Description('Crea un administrador con contraseña aleatoria o designa propietario a un administrador existente')]
class CrearAdmin extends Command
{
    public function handle(BitacoraService $bitacora): int
    {
        $email = Str::lower($this->argument('email'));
        $existente = User::query()->where('email', $email)->first();

        if ($existente) {
            return $this->designarPropietario($existente, $bitacora);
        }

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error('El correo no es válido.');

            return self::FAILURE;
        }

        $password = Str::password(16, symbols: false);

        $admin = new User(['nombre' => $this->option('nombre'), 'email' => $email, 'password' => $password]);
        $admin->forceFill([
            'rol' => Rol::Admin,
            'es_propietario' => (bool) $this->option('propietario'),
            'email_verified_at' => now(),
            'debe_cambiar_password' => true,
        ])->save();

        $bitacora->registrar('usuario_creado', $admin, null, ['email' => $email, 'rol' => Rol::Admin->value, 'propietario' => $admin->es_propietario, 'origen' => 'consola']);

        $this->info(($admin->es_propietario ? 'Propietario' : 'Administrador')." creado: {$email}");
        $this->line("Contraseña temporal: {$password}");
        $this->warn('El sistema pedirá cambiarla en el primer ingreso.');

        return self::SUCCESS;
    }

    private function designarPropietario(User $usuario, BitacoraService $bitacora): int
    {
        if (! $this->option('propietario')) {
            $this->error("Ya existe una cuenta con {$usuario->email}. Usa --propietario para designarla propietaria.");

            return self::FAILURE;
        }

        if ($usuario->rol !== Rol::Admin) {
            $this->error('Solo un administrador puede ser propietario. Primero asígnale el rol de administrador.');

            return self::FAILURE;
        }

        $usuario->forceFill(['es_propietario' => true])->save();
        $bitacora->registrar('propietario_designado', $usuario, ['propietario' => false], ['propietario' => true, 'origen' => 'consola']);

        $this->info("{$usuario->email} ahora es propietario de la tienda.");

        return self::SUCCESS;
    }
}
