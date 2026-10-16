<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Services\CompatibilidadService;
use App\Support\Configurador\Hallazgo;
use App\Support\Configurador\Seleccion;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompatibilidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompatibilidadService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogoSeeder::class);
        $this->servicio = app(CompatibilidadService::class);
    }

    /**
     * @param  array<string, int>  $productos  slug => cantidad
     */
    private function seleccion(array $productos): Seleccion
    {
        $ids = [];
        foreach (Producto::query()->with('categoria')->whereIn('slug', array_keys($productos))->get() as $producto) {
            $ids[$producto->tipoComponente()->value][$producto->id] = $productos[$producto->slug];
        }

        $this->assertCount(count($productos), collect($ids)->flatten(), 'Algún slug de prueba no existe.');

        return Seleccion::desde($ids);
    }

    /**
     * @param  array<string, int>  $productos
     * @return array<string, string> codigo => nivel
     */
    private function hallazgos(array $productos): array
    {
        return collect($this->servicio->evaluar($this->seleccion($productos)))
            ->mapWithKeys(fn (Hallazgo $h) => [$h->codigo => $h->nivel])
            ->all();
    }

    public function test_una_configuracion_correcta_no_tiene_errores_y_se_puede_comprar(): void
    {
        $seleccion = $this->seleccion([
            'amd-ryzen-5-7600' => 1,
            'msi-b650-gaming-plus-wifi' => 1,
            'kingston-fury-beast-ddr5-32gb-2x16-6000mhz' => 1,
            'samsung-990-pro-1tb-nvme-m2' => 1,
            'msi-geforce-rtx-4060-ventus-2x-8gb' => 1,
            'nzxt-h5-flow' => 1,
            'msi-mag-a750gl-pcie5-750w-80-plus-gold' => 1,
        ]);

        $this->assertSame([], $this->servicio->evaluar($seleccion));
        $this->assertTrue($this->servicio->puedeComprarse($seleccion));
        $this->assertSame(302, $this->servicio->consumoEstimado($seleccion));
        $this->assertSame(550, $this->servicio->fuenteRecomendada($seleccion)); // la RTX 4060 pide 550 W
    }

    /**
     * @param  array<string, int>  $productos
     */
    #[DataProvider('casos')]
    public function test_cada_regla_detecta_su_problema(array $productos, string $codigo, string $nivel): void
    {
        $hallazgos = $this->hallazgos($productos);

        $this->assertArrayHasKey($codigo, $hallazgos, "No se detectó {$codigo}.");
        $this->assertSame($nivel, $hallazgos[$codigo]);
    }

    /**
     * @return array<string, array{0: array<string, int>, 1: string, 2: string}>
     */
    public static function casos(): array
    {
        $e = Hallazgo::ERROR;
        $a = Hallazgo::ADVERTENCIA;

        return [
            'R01 socket CPU/placa' => [['amd-ryzen-5-7600' => 1, 'msi-pro-b760m-a-wifi-ddr4' => 1], 'R01', $e],
            'R02 memoria/placa' => [['msi-b650-gaming-plus-wifi' => 1, 'kingston-fury-beast-ddr4-16gb-2x8-3200mhz' => 1], 'R02', $e],
            'R03 memoria/CPU' => [['amd-ryzen-5-5600' => 1, 'kingston-fury-beast-ddr5-32gb-2x16-6000mhz' => 1], 'R03', $e],
            'R04 ranuras RAM' => [['asus-rog-strix-b650e-i-gaming-wifi' => 1, 'kingston-fury-beast-ddr5-32gb-2x16-6000mhz' => 2], 'R04', $e],
            'R05 capacidad RAM' => [['gigabyte-a620m-gaming-x' => 1, 'gskill-trident-z5-rgb-ddr5-64gb-2x32-6400mhz' => 2], 'R05', $e],
            'R06 placa/gabinete' => [['msi-b650-gaming-plus-wifi' => 1, 'cooler-master-masterbox-q300l' => 1], 'R06', $e],
            'R07 largo GPU' => [['xfx-speedster-merc-319-radeon-rx-7800-xt-16gb' => 1, 'cooler-master-masterbox-nr200p' => 1], 'R07', $e],
            'R08 falta GPU' => [['intel-core-i5-14400f' => 1], 'R08', $e],
            'R09 falta disipador' => [['amd-ryzen-7-7800x3d' => 1], 'R09', $e],
            'R10 socket disipador' => [['intel-core-ultra-5-245k' => 1, 'corsair-icue-h100i-elite-240' => 1], 'R10', $e],
            'R11 altura disipador' => [['noctua-nh-d15-g2' => 1, 'cooler-master-masterbox-nr200p' => 1], 'R11', $e],
            'R12 radiador' => [['arctic-liquid-freezer-iii-360' => 1, 'cooler-master-masterbox-q300l' => 1], 'R12', $e],
            'R15 margen de fuente' => [['corsair-cx650-650w-80-plus-bronze' => 1, 'xfx-speedster-merc-319-radeon-rx-7800-xt-16gb' => 1], 'R15', $a],
            'R16 fuente ATX en gabinete SFX' => [['corsair-rm850e-850w-80-plus-gold' => 1, 'cooler-master-masterbox-nr200p' => 1], 'R16', $e],
            'R16 fuente SFX en gabinete ATX' => [['cooler-master-v-sfx-gold-750w' => 1, 'lian-li-lancool-216' => 1], 'R16', $a],
            'R17 ranuras M.2' => [['gigabyte-a620m-gaming-x' => 1, 'samsung-990-pro-1tb-nvme-m2' => 1, 'kingston-nv3-1tb-nvme-m2' => 1], 'R17', $e],
            'R18 puertos SATA' => [['asus-rog-strix-b650e-i-gaming-wifi' => 1, 'crucial-mx500-1tb-sata-25' => 2, 'wd-blue-2tb-hdd-7200-rpm' => 1], 'R18', $e],
            'R18 bahías 3.5"' => [['cooler-master-masterbox-nr200p' => 1, 'wd-blue-2tb-hdd-7200-rpm' => 2], 'R18', $e],
        ];
    }

    public function test_r13_advierte_si_el_disipador_no_soporta_el_tdp(): void
    {
        $cpu = Producto::query()->where('slug', 'intel-core-i7-14700k')->value('id');
        DB::table('especificaciones_cpu')->where('producto_id', $cpu)->update(['tdp_w' => 253]);

        $this->assertSame(Hallazgo::ADVERTENCIA, $this->hallazgos(['intel-core-i7-14700k' => 1, 'deepcool-ak400' => 1])['R13'] ?? null);
    }

    public function test_r14_marca_error_si_la_fuente_no_cubre_el_consumo(): void
    {
        $fuente = Producto::query()->where('slug', 'evga-600-w1-600w-80-plus')->value('id');
        DB::table('especificaciones_fuente')->where('producto_id', $fuente)->update(['potencia_w' => 300]);

        $hallazgos = $this->hallazgos(['intel-core-i7-14700k' => 1, 'xfx-speedster-merc-319-radeon-rx-7800-xt-16gb' => 1, 'evga-600-w1-600w-80-plus' => 1]);

        $this->assertSame(Hallazgo::ERROR, $hallazgos['R14'] ?? null);
    }

    public function test_solo_considera_compatibles_las_piezas_que_no_generan_errores(): void
    {
        $conPlacaAm5 = $this->seleccion(['msi-b650-gaming-plus-wifi' => 1]);

        $this->assertTrue($this->servicio->esCompatible($conPlacaAm5, Producto::query()->where('slug', 'amd-ryzen-5-7600')->first()));
        $this->assertFalse($this->servicio->esCompatible($conPlacaAm5, Producto::query()->where('slug', 'intel-core-i5-14400f')->first()));
        // Un procesador sin gráficos no se oculta: el faltante de GPU se resuelve en otro paso.
        $this->assertTrue($this->servicio->esCompatible($this->seleccion(['msi-pro-b760m-a-wifi-ddr4' => 1]), Producto::query()->where('slug', 'intel-core-i5-14400f')->first()));
    }

    public function test_faltan_componentes_obligatorios_o_existencia_impide_comprar(): void
    {
        $incompleta = $this->seleccion(['amd-ryzen-5-7600' => 1]);
        $this->assertFalse($this->servicio->puedeComprarse($incompleta));
        $this->assertCount(5, $this->servicio->faltantes($incompleta));

        $sinStock = $this->seleccion(['amd-ryzen-7-7800x3d' => 1]);
        $this->assertCount(1, $this->servicio->sinExistencia($sinStock));
    }
}
