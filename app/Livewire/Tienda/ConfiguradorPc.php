<?php

namespace App\Livewire\Tienda;

use App\Enums\TipoComponente;
use App\Models\Configuracion;
use App\Models\ParametroSistema;
use App\Models\Producto;
use App\Services\CarritoService;
use App\Services\CompatibilidadService;
use App\Support\Configurador\Configurador;
use App\Support\Configurador\Seleccion;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * Configurador de PC a la medida: el cliente elige pieza por pieza y solo ve
 * productos compatibles con lo ya elegido y con existencia.
 */
class ConfiguradorPc extends Component
{
    /** @var array<string, array<int, int>> tipo => [producto_id => cantidad] */
    #[Session]
    public array $seleccion = [];

    #[Session]
    public bool $incluyeArmado = true;

    #[Session]
    public ?string $codigoGuardado = null;

    #[Session]
    public string $nombre = 'Mi PC ForjaPC';

    public string $paso = 'cpu';

    public string $buscar = '';

    public ?string $mensaje = null;

    public function mount(?Configuracion $configuracion = null): void
    {
        if ($configuracion?->exists) {
            $configuracion->load('componentes');
            $this->seleccion = $configuracion->seleccion();
            $this->incluyeArmado = $configuracion->incluye_armado;
            $esPropia = Auth::id() === $configuracion->usuario_id;
            $this->codigoGuardado = $esPropia ? $configuracion->codigo : null;
            $this->nombre = $esPropia ? $configuracion->nombre : $configuracion->nombre.' (copia)';
        }

        $this->paso = $this->siguientePasoPendiente() ?? 'cpu';
    }

    public function irA(string $paso): void
    {
        if (TipoComponente::tryFrom($paso) !== null && in_array(TipoComponente::from($paso), Configurador::PASOS, true)) {
            $this->paso = $paso;
            $this->buscar = '';
        }
    }

    public function elegir(int $productoId, CompatibilidadService $compatibilidad): void
    {
        $producto = Producto::query()->visibles()->with(Seleccion::RELACIONES)->find($productoId);
        $tipo = $producto?->tipoComponente();

        if ($producto === null || $tipo === null || ! in_array($tipo, Configurador::PASOS, true)) {
            return;
        }

        if (! $producto->estaDisponible() || ! $compatibilidad->esCompatible(Seleccion::desde($this->seleccion), $producto)) {
            $this->mensaje = 'Ese producto no es compatible con tu selección o ya no tiene existencia.';

            return;
        }

        if ($tipo === TipoComponente::Almacenamiento) {
            if (count($this->seleccion[$tipo->value] ?? []) >= Configurador::maximo($tipo) && ! isset($this->seleccion[$tipo->value][$producto->id])) {
                return;
            }
            $this->seleccion[$tipo->value][$producto->id] = 1;
        } else {
            $this->seleccion[$tipo->value] = [$producto->id => 1];
            $this->paso = $this->siguientePasoPendiente($tipo) ?? $this->paso;
        }

        $this->buscar = '';
    }

    public function quitar(string $tipo, int $productoId): void
    {
        unset($this->seleccion[$tipo][$productoId]);

        if (($this->seleccion[$tipo] ?? []) === []) {
            unset($this->seleccion[$tipo]);
        }
    }

    public function cambiarCantidad(string $tipo, int $productoId, int $cantidad): void
    {
        $tipoComponente = TipoComponente::tryFrom($tipo);

        if ($tipoComponente === null || ! isset($this->seleccion[$tipo][$productoId])) {
            return;
        }

        $disponible = (int) Producto::query()->whereKey($productoId)->value(DB::raw('existencia - reservado'));
        $this->seleccion[$tipo][$productoId] = max(1, min($cantidad, Configurador::maximo($tipoComponente), max(1, $disponible)));
    }

    public function vaciar(): void
    {
        $this->reset('seleccion', 'codigoGuardado', 'buscar');
        $this->nombre = 'Mi PC ForjaPC';
        $this->paso = 'cpu';
    }

    public function guardar(): mixed
    {
        if (! Auth::check()) {
            session()->put('url.intended', route('tienda.configurador'));

            return $this->redirectRoute('login');
        }

        $this->validate(['nombre' => ['required', 'string', 'max:80']], [], ['nombre' => 'nombre de la configuración']);

        $lineas = Seleccion::desde($this->seleccion)->lineas();
        if ($lineas->isEmpty()) {
            $this->addError('nombre', 'Elige al menos un componente antes de guardar.');

            return null;
        }

        $configuracion = DB::transaction(function () use ($lineas) {
            $configuracion = $this->codigoGuardado
                ? Configuracion::query()->where('codigo', $this->codigoGuardado)->where('usuario_id', Auth::id())->first()
                : null;

            $configuracion ??= new Configuracion;
            $configuracion->usuario_id = Auth::id();
            $configuracion->fill(['nombre' => $this->nombre, 'incluye_armado' => $this->incluyeArmado])->save();

            $configuracion->componentes()->delete();
            foreach ($lineas as $linea) {
                $configuracion->componentes()->create([
                    'tipo' => $linea['tipo']->value,
                    'producto_id' => $linea['producto']->id,
                    'cantidad' => $linea['cantidad'],
                ]);
            }

            return $configuracion;
        });

        $this->codigoGuardado = $configuracion->codigo;
        $this->mensaje = 'Configuración guardada. Puedes compartirla con el enlace del resumen.';

        return null;
    }

    public function agregarAlCarrito(CarritoService $carrito): mixed
    {
        try {
            $carrito->agregarConfiguracion(Seleccion::desde($this->seleccion), $this->nombre, $this->incluyeArmado);
        } catch (DomainException $e) {
            $this->mensaje = $e->getMessage();

            return null;
        }

        $this->reset('seleccion', 'codigoGuardado');
        $this->nombre = 'Mi PC ForjaPC';
        $this->paso = 'cpu';

        return $this->redirectRoute('carrito');
    }

    public function render(CompatibilidadService $compatibilidad): View
    {
        $seleccion = Seleccion::desde($this->seleccion);
        $pasoActual = TipoComponente::tryFrom($this->paso) ?? TipoComponente::Cpu;

        $candidatos = Producto::query()->visibles()->conDisponibilidad()
            ->whereHas('categoria', fn (Builder $q) => $q->where('tipo_componente', $pasoActual))
            ->with([...Seleccion::RELACIONES, 'marca', 'imagenes'])
            ->when(trim($this->buscar) !== '', fn (Builder $q) => $q->where('nombre', 'like', '%'.trim($this->buscar).'%'))
            ->orderBy('precio')
            ->get();

        $compatibles = $candidatos->filter(fn (Producto $p) => $compatibilidad->esCompatible($seleccion, $p))->values();
        $hallazgos = $compatibilidad->evaluar($seleccion);
        $costoArmado = $this->incluyeArmado ? (float) ParametroSistema::valor('costo_servicio_armado', 250) : 0.0;

        return view('livewire.tienda.configurador-pc', [
            'pasos' => Configurador::PASOS,
            'pasoActual' => $pasoActual,
            'seleccionado' => $seleccion,
            'candidatos' => $compatibles,
            'ocultos' => $candidatos->count() - $compatibles->count(),
            'hallazgos' => $hallazgos,
            'faltantes' => $compatibilidad->faltantes($seleccion),
            'sinExistencia' => $compatibilidad->sinExistencia($seleccion),
            'consumo' => $compatibilidad->consumoEstimado($seleccion),
            'fuenteRecomendada' => $compatibilidad->fuenteRecomendada($seleccion),
            'puedeComprarse' => $compatibilidad->puedeComprarse($seleccion),
            'totalComponentes' => $seleccion->totalComponentes(),
            'costoArmado' => $costoArmado,
            'reglas' => CompatibilidadService::REGLAS,
        ]);
    }

    /**
     * Primer paso sin elegir después del indicado (o desde el inicio).
     */
    private function siguientePasoPendiente(?TipoComponente $despuesDe = null): ?string
    {
        $pasos = Configurador::PASOS;
        $inicio = $despuesDe ? array_search($despuesDe, $pasos, true) + 1 : 0;

        foreach (array_slice($pasos, $inicio) as $paso) {
            if (! isset($this->seleccion[$paso->value])) {
                return $paso->value;
            }
        }

        return $despuesDe?->value;
    }
}
