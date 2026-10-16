<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartamentoSeeder::class,
            ParametroSistemaSeeder::class,
        ]);

        // En producción el administrador se crea con "php artisan forjapc:crear-admin".
        if (app()->isProduction()) {
            $this->call(CatalogoSeeder::class);

            return;
        }

        // Una cuenta por rol para pruebas locales; la contraseña de todas es "password".
        $cuentas = [
            [Rol::Admin, 'Administrador ForjaPC', 'admin@forjapc.test'],
            [Rol::Vendedor, 'Ventas ForjaPC', 'ventas@forjapc.test'],
            [Rol::Tecnico, 'Taller ForjaPC', 'taller@forjapc.test'],
            [Rol::Contador, 'Contabilidad ForjaPC', 'contabilidad@forjapc.test'],
            [Rol::Cliente, 'Cliente de Prueba', 'cliente@forjapc.test'],
        ];

        foreach ($cuentas as [$rol, $nombre, $email]) {
            User::factory()->conRol($rol)->create(['nombre' => $nombre, 'email' => $email]);
        }

        $this->call(CatalogoSeeder::class);
    }
}
