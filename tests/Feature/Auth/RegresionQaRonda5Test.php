<?php

namespace Tests\Feature\Auth;

use App\Enums\Rol;
use App\Models\User;
use App\Notifications\CodigoAcceso;
use App\Services\SegundoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Una prueba por cada defecto de la ronda 5 de QA (segundo factor e inactividad).
 */
class RegresionQaRonda5Test extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        config(['forjapc.dos_pasos.activo' => true]);
        Notification::fake();
        $this->vendedor = User::factory()->conRol(Rol::Vendedor)->create();
    }

    private function fallar(int $veces): void
    {
        for ($i = 0; $i < $veces; $i++) {
            $this->actingAs($this->vendedor)->get('/verificacion-dos-pasos');
            $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => 'x']);
        }
    }

    public function test_def_045_demasiados_fallos_en_la_hora_bloquean_la_cuenta(): void
    {
        $this->fallar(SegundoFactorService::FALLOS_POR_HORA - 1);
        $this->assertNull($this->vendedor->refresh()->bloqueado_hasta);

        $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => 'x'])->assertRedirect('/login');

        $this->assertTrue($this->vendedor->refresh()->bloqueado_hasta->isFuture());
        $this->assertGuest();
        $this->assertDatabaseHas('bitacora_auditoria', ['accion' => 'cuenta_bloqueada', 'entidad_id' => $this->vendedor->id]);
    }

    public function test_def_046_recordarme_no_reabre_la_sesion_del_personal(): void
    {
        config(['forjapc.dos_pasos.activo' => false]);
        $this->vendedor->forceFill(['remember_token' => 'token-recordarme'])->save();

        // La sesión ya venció: solo queda la cookie de «Recordarme».
        $recordar = $this->vendedor->id.'|token-recordarme|'.Auth::guard('web')->hashPasswordForCookie($this->vendedor->getAuthPassword());
        $this->withCookie(Auth::guard('web')->getRecallerName(), $recordar)->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_def_046_al_cliente_si_le_sirve_recordarme(): void
    {
        $cliente = User::factory()->create(['remember_token' => 'token-cliente']);
        $this->withCookie(Auth::guard('web')->getRecallerName(), $cliente->id.'|token-cliente|'.Auth::guard('web')->hashPasswordForCookie($cliente->getAuthPassword()))->get('/mi-cuenta')->assertOk();
    }

    public function test_def_047_los_reenvios_no_comparten_contador_con_los_intentos(): void
    {
        $this->actingAs($this->vendedor)->get('/verificacion-dos-pasos');
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => 'x']);
        }

        $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos/reenviar')->assertRedirect();
    }

    public function test_def_048_un_fallo_no_extiende_el_vencimiento(): void
    {
        $this->actingAs($this->vendedor)->get('/verificacion-dos-pasos');
        $vence = Cache::get("dos_pasos:{$this->vendedor->id}")['vence'];

        $this->travel(3)->minutes();
        $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => 'x']);

        $this->assertSame($vence, Cache::get("dos_pasos:{$this->vendedor->id}")['vence']);
    }

    public function test_def_049_un_cliente_no_genera_codigos(): void
    {
        $cliente = User::factory()->create();

        $this->actingAs($cliente)->get('/verificacion-dos-pasos')->assertRedirect(route('dashboard'));
        $this->actingAs($cliente)->post('/verificacion-dos-pasos', ['codigo' => '123456'])->assertNotFound();
        $this->actingAs($cliente)->post('/verificacion-dos-pasos/reenviar')->assertNotFound();
        Notification::assertNotSentTo($cliente, CodigoAcceso::class);
    }

    public function test_def_050_sin_codigo_no_se_revela_que_registros_existen(): void
    {
        $this->actingAs($this->vendedor)->get('/admin/compras/999999')->assertRedirect('/verificacion-dos-pasos');
    }
}
