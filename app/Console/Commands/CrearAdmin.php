<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('forjapc:crear-admin {email : Correo del administrador} {--nombre=Administrador ForjaPC : Nombre completo}')]
#[Description('Crea una cuenta de administrador con una contraseña aleatoria (para producción)')]
class CrearAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower($this->argument('email'));

        $validador = Validator::make(['email' => $email], ['email' => ['required', 'email', 'unique:usuarios,email']]);

        if ($validador->fails()) {
            $this->error($validador->errors()->first('email'));

            return self::FAILURE;
        }

        $password = Str::password(16, symbols: false);

        $admin = new User(['nombre' => $this->option('nombre'), 'email' => $email, 'password' => $password]);
        $admin->forceFill(['rol' => Rol::Admin, 'email_verified_at' => now(), 'debe_cambiar_password' => true])->save();

        $this->info("Administrador creado: {$email}");
        $this->line("Contraseña temporal: {$password}");
        $this->warn('El sistema pedirá cambiarla en el primer ingreso.');

        return self::SUCCESS;
    }
}
