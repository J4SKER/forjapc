<?php

namespace Tests\Feature\Admin;

use App\Enums\Rol;
use App\Enums\TipoComponente;
use App\Models\BitacoraAuditoria;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Socket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductoAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    private Categoria $procesadores;

    private Marca $marca;

    private Socket $socket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendedor = User::factory()->conRol(Rol::Vendedor)->create();
        $this->procesadores = Categoria::create(['nombre' => 'Procesadores', 'slug' => 'procesadores', 'tipo_componente' => TipoComponente::Cpu]);
        $this->marca = Marca::create(['nombre' => 'AMD', 'slug' => 'amd']);
        $this->socket = Socket::create(['nombre' => 'AM5']);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return array_replace_recursive([
            'categoria_id' => $this->procesadores->id,
            'marca_id' => $this->marca->id,
            'sku' => 'cpu-0001',
            'nombre' => 'AMD Ryzen 5 7600',
            'precio' => '1750.00',
            'garantia_meses' => 36,
            'stock_minimo' => 2,
            'consumo_watts' => 65,
            'activo' => '1',
            'esp' => [
                'socket_id' => $this->socket->id, 'nucleos' => 6, 'hilos' => 12, 'frecuencia_base_ghz' => 3.8,
                'frecuencia_turbo_ghz' => 5.1, 'tdp_w' => 65, 'tipo_memoria' => 'DDR5', 'graficos_integrados' => '1', 'incluye_disipador' => '0',
            ],
        ], $cambios);
    }

    public function test_crea_un_producto_con_especificaciones_e_imagen(): void
    {
        Storage::fake('public');

        $this->actingAs($this->vendedor)
            ->post('/admin/productos', $this->datos(['imagenes' => [UploadedFile::fake()->image('ryzen.jpg', 600, 600)]]))
            ->assertRedirect();

        $producto = Producto::query()->where('sku', 'CPU-0001')->firstOrFail();
        $this->assertSame('amd-ryzen-5-7600', $producto->slug);
        $this->assertSame(0, $producto->existencia);
        $this->assertSame(6, $producto->especificacionCpu->nucleos);
        $this->assertTrue($producto->especificacionCpu->graficos_integrados);
        $this->assertCount(1, $producto->imagenes);
        Storage::disk('public')->assertExists($producto->imagenes->first()->ruta);
    }

    public function test_exige_las_especificaciones_del_tipo_de_componente(): void
    {
        $datos = $this->datos();
        unset($datos['esp']['socket_id'], $datos['esp']['nucleos']);

        $this->actingAs($this->vendedor)->post('/admin/productos', $datos)
            ->assertSessionHasErrors(['esp.socket_id', 'esp.nucleos']);
    }

    public function test_cambiar_el_precio_queda_en_la_bitacora(): void
    {
        $this->actingAs($this->vendedor)->post('/admin/productos', $this->datos());
        $producto = Producto::query()->firstOrFail();

        $this->actingAs($this->vendedor)->put("/admin/productos/{$producto->id}", $this->datos(['precio' => '1650.00', 'precio_anterior' => '1750.00']))
            ->assertRedirect();

        $this->assertDatabaseHas(BitacoraAuditoria::class, ['accion' => 'precio_cambiado', 'entidad_id' => $producto->id]);
        $this->assertTrue($producto->refresh()->enOferta());
    }

    public function test_el_formulario_no_permite_cambiar_la_existencia(): void
    {
        $this->actingAs($this->vendedor)->post('/admin/productos', $this->datos(['existencia' => 500, 'costo_promedio' => 1]));

        $producto = Producto::query()->firstOrFail();
        $this->assertSame(0, $producto->existencia);
        $this->assertEquals(0.0, (float) $producto->costo_promedio);
    }

    public function test_eliminar_es_logico(): void
    {
        $this->actingAs($this->vendedor)->post('/admin/productos', $this->datos());
        $producto = Producto::query()->firstOrFail();

        $this->actingAs($this->vendedor)->delete("/admin/productos/{$producto->id}")->assertRedirect();

        $this->assertSoftDeleted($producto);
    }

    public function test_el_tecnico_y_el_contador_no_administran_el_catalogo(): void
    {
        foreach ([Rol::Tecnico, Rol::Contador] as $rol) {
            $this->actingAs(User::factory()->conRol($rol)->create())->get('/admin/productos')->assertForbidden();
        }
    }
}
