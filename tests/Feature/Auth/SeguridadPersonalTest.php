<?php

namespace Tests\Feature\Auth;

use App\Enums\Rol;
use App\Http\Middleware\CerrarSesionInactiva;
use App\Models\User;
use App\Notifications\CodigoAcceso;
use App\Services\SegundoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Segundo factor del personal, cierre por inactividad y política de contenido (CSP).
 */
class SeguridadPersonalTest extends TestCase
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

    private function codigoEnviado(): string
    {
        $codigo = null;
        Notification::assertSentTo($this->vendedor, CodigoAcceso::class, function (CodigoAcceso $n) use (&$codigo) {
            $codigo = $n->codigo;

            return true;
        });

        return $codigo;
    }

    public function test_el_personal_debe_ingresar_el_codigo_antes_de_usar_el_panel(): void
    {
        $this->actingAs($this->vendedor)->get('/admin/pedidos')->assertRedirect('/verificacion-dos-pasos');
        $this->actingAs($this->vendedor)->get('/verificacion-dos-pasos')->assertOk()->assertSee('Revisa tu correo');

        $codigo = $this->codigoEnviado();
        $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => $codigo])->assertRedirect('/admin/pedidos');
        $this->actingAs($this->vendedor)->get('/admin/pedidos')->assertOk();
    }

    public function test_el_cliente_no_necesita_segundo_factor(): void
    {
        $this->actingAs(User::factory()->create())->get('/mi-cuenta')->assertOk();
        Notification::assertNothingSent();
    }

    public function test_un_codigo_incorrecto_se_rechaza_y_tras_cinco_intentos_se_renueva(): void
    {
        $this->actingAs($this->vendedor)->get('/verificacion-dos-pasos');
        $primero = $this->codigoEnviado();
        $incorrecto = $primero === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => $incorrecto])->assertSessionHasErrors(['codigo' => 'El código no es correcto.']);
        }
        $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => $incorrecto])->assertSessionHasErrors(['codigo' => 'Demasiados intentos. Te enviamos un código nuevo.']);

        // El primer código ya no sirve aunque fuera el correcto.
        $this->actingAs($this->vendedor)->post('/verificacion-dos-pasos', ['codigo' => $primero])->assertSessionHasErrors('codigo');
        Notification::assertSentToTimes($this->vendedor, CodigoAcceso::class, 2);
    }

    public function test_un_post_sin_segundo_factor_se_rechaza(): void
    {
        $this->actingAs($this->vendedor)->post('/admin/marcas', ['nombre' => 'Nueva'])->assertForbidden();
    }

    public function test_el_equipo_recordado_no_pide_codigo_hasta_que_cambie_la_contrasena(): void
    {
        $cookie = app(SegundoFactorService::class)->recordarEquipo($this->vendedor);

        $this->actingAs($this->vendedor)->withCookie(SegundoFactorService::COOKIE, $cookie->getValue())->get('/admin')->assertOk();

        // Con la contraseña nueva, en un inicio de sesión nuevo la cookie anterior ya no vale.
        $this->vendedor->forceFill(['password' => 'OtraClave12345'])->save();
        $this->flushSession();
        $this->actingAs($this->vendedor->refresh())->withCookie(SegundoFactorService::COOKIE, $cookie->getValue())->get('/admin')->assertRedirect('/verificacion-dos-pasos');
    }

    public function test_la_sesion_del_personal_se_cierra_por_inactividad(): void
    {
        config(['forjapc.dos_pasos.activo' => false]);

        $this->actingAs($this->vendedor)->withSession([CerrarSesionInactiva::SESION => time() - 31 * 60])
            ->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_la_actividad_mantiene_la_sesion_y_el_cliente_no_se_ve_afectado(): void
    {
        config(['forjapc.dos_pasos.activo' => false]);

        $this->actingAs($this->vendedor)->withSession([CerrarSesionInactiva::SESION => time() - 10 * 60])->get('/admin')->assertOk();
        $this->actingAs(User::factory()->create())->withSession([CerrarSesionInactiva::SESION => time() - 90 * 60])->get('/mi-cuenta')->assertOk();
    }

    public function test_las_respuestas_llevan_politica_de_contenido(): void
    {
        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString('https://*.paypal.com', $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }
}
