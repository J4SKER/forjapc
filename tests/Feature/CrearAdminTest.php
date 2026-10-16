<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrearAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_un_administrador_verificado(): void
    {
        $this->artisan('forjapc:crear-admin', ['email' => 'Jefe@ForjaPC.me'])
            ->expectsOutputToContain('Administrador creado: jefe@forjapc.me')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'jefe@forjapc.me')->firstOrFail();
        $this->assertSame(Rol::Admin, $admin->rol);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_no_duplica_un_correo_existente(): void
    {
        User::factory()->create(['email' => 'jefe@forjapc.me']);

        $this->artisan('forjapc:crear-admin', ['email' => 'jefe@forjapc.me'])->assertFailed();
    }
}
