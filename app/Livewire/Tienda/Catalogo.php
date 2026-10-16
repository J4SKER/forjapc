<?php

namespace App\Livewire\Tienda;

use App\Enums\TipoComponente;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Socket;
use App\Services\BuscadorProductos;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de productos con filtros por marca, precio, disponibilidad y atributos técnicos.
 * Se usa en las páginas de categoría y en los resultados de búsqueda.
 */
class Catalogo extends Component
{
    use WithPagination;

    /**
     * Filtros técnicos por tipo de componente: columna de la tabla de especificaciones => etiqueta.
     *
     * @var array<string, array<string, string>>
     */
    private const FILTROS_TECNICOS = [
        'cpu' => ['socket_id' => 'Socket', 'tipo_memoria' => 'Memoria compatible'],
        'placa' => ['socket_id' => 'Socket', 'formato' => 'Formato', 'tipo_memoria' => 'Memoria'],
        'ram' => ['tipo' => 'Tipo', 'capacidad_total_gb' => 'Capacidad'],
        'gpu' => ['vram_gb' => 'Memoria de video'],
        'almacenamiento' => ['tipo' => 'Tipo', 'capacidad_gb' => 'Capacidad'],
        'fuente' => ['potencia_w' => 'Potencia', 'formato' => 'Formato'],
        'gabinete' => ['formato_max_placa' => 'Placa máxima'],
        'disipador' => ['tipo' => 'Tipo'],
        'monitor' => ['resolucion' => 'Resolución', 'frecuencia_hz' => 'Frecuencia', 'panel' => 'Panel'],
    ];

    private const TABLAS = [
        'cpu' => 'especificaciones_cpu', 'placa' => 'especificaciones_placa_base', 'ram' => 'especificaciones_ram',
        'gpu' => 'especificaciones_gpu', 'almacenamiento' => 'especificaciones_almacenamiento',
        'fuente' => 'especificaciones_fuente', 'gabinete' => 'especificaciones_gabinete',
        'disipador' => 'especificaciones_disipador', 'monitor' => 'especificaciones_monitor',
    ];

    public ?int $categoriaId = null;

    #[Url(except: '')]
    public string $q = '';

    /** @var list<int> */
    #[Url(as: 'marca', except: [])]
    public array $marcas = [];

    #[Url(except: '')]
    public string $min = '';

    #[Url(except: '')]
    public string $max = '';

    #[Url(as: 'disponibles', except: false)]
    public bool $soloDisponibles = false;

    #[Url(except: 'relevancia')]
    public string $orden = 'relevancia';

    /** @var array<string, string> */
    #[Url(as: 'f', except: [])]
    public array $tecnicos = [];

    public function mount(?Categoria $categoria = null, string $q = ''): void
    {
        $this->categoriaId = $categoria?->id;
        $this->q = $this->q !== '' ? $this->q : $q;
    }

    public function updating(string $propiedad): void
    {
        if ($propiedad !== 'paginators') {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset('marcas', 'min', 'max', 'soloDisponibles', 'tecnicos');
        $this->resetPage();
    }

    public function render(BuscadorProductos $buscador): View
    {
        $categoria = $this->categoriaId ? Categoria::find($this->categoriaId) : null;
        $tipo = $categoria?->tipo_componente;

        $base = Producto::query()->visibles()
            ->when($categoria, fn (Builder $q) => $q->where('categoria_id', $categoria->id))
            ->when(trim($this->q) !== '', fn (Builder $q) => $buscador->aplicar($q, $this->q));

        $productos = (clone $base)
            ->with(['categoria', 'marca', 'imagenes', ...$this->relacionesDeEspecificacion($tipo)])
            ->when($this->marcas !== [], fn (Builder $q) => $q->whereIn('marca_id', $this->marcas))
            ->when(is_numeric($this->min), fn (Builder $q) => $q->where('precio', '>=', (float) $this->min))
            ->when(is_numeric($this->max), fn (Builder $q) => $q->where('precio', '<=', (float) $this->max))
            ->when($this->soloDisponibles, fn (Builder $q) => $q->conDisponibilidad())
            ->when($tipo, fn (Builder $q) => $this->aplicarFiltrosTecnicos($q, $tipo))
            ->tap(fn (Builder $q) => $this->ordenar($q))
            ->paginate(12);

        $sugerencia = $productos->total() === 0 && trim($this->q) !== '' ? $buscador->sugerencia($this->q) : null;

        return view('livewire.tienda.catalogo', [
            'categoria' => $categoria,
            'productos' => $productos,
            'marcasDisponibles' => Marca::query()
                ->whereIn('id', (clone $base)->select('marca_id'))
                ->orderBy('nombre')->get(['id', 'nombre']),
            'filtrosTecnicos' => $tipo ? $this->opcionesTecnicas($base, $tipo) : [],
            'sugerencia' => $sugerencia,
            'hayFiltros' => $this->marcas !== [] || $this->min !== '' || $this->max !== '' || $this->soloDisponibles || array_filter($this->tecnicos) !== [],
        ]);
    }

    /**
     * Relaciones a precargar para mostrar el resumen técnico de cada tarjeta.
     *
     * @return list<string>
     */
    private function relacionesDeEspecificacion(?TipoComponente $tipo): array
    {
        if ($tipo === null) {
            return [];
        }

        return match ($tipo) {
            TipoComponente::Cpu, TipoComponente::Placa => [$tipo->relacion().'.socket'],
            TipoComponente::Disipador => [$tipo->relacion().'.sockets'],
            default => [$tipo->relacion()],
        };
    }

    /**
     * @param  Builder<Producto>  $consulta
     */
    private function aplicarFiltrosTecnicos(Builder $consulta, TipoComponente $tipo): void
    {
        $permitidos = self::FILTROS_TECNICOS[$tipo->value] ?? [];

        foreach (array_filter($this->tecnicos, fn ($valor) => $valor !== '' && $valor !== null) as $campo => $valor) {
            if (! array_key_exists($campo, $permitidos)) {
                continue;
            }

            $consulta->whereHas($tipo->relacion(), fn (Builder $q) => $q->where($campo, $valor));
        }
    }

    /**
     * Valores existentes de cada filtro técnico con la cantidad de productos.
     *
     * @param  Builder<Producto>  $base
     * @return array<string, array{etiqueta: string, opciones: list<array{valor: string, texto: string, total: int}>}>
     */
    private function opcionesTecnicas(Builder $base, TipoComponente $tipo): array
    {
        $tabla = self::TABLAS[$tipo->value];
        $ids = (clone $base)->select('productos.id');
        $sockets = Socket::query()->pluck('nombre', 'id');
        $resultado = [];

        foreach (self::FILTROS_TECNICOS[$tipo->value] as $campo => $etiqueta) {
            $opciones = DB::table($tabla)
                ->whereIn('producto_id', $ids)
                ->select($campo.' as valor', DB::raw('COUNT(*) as total'))
                ->groupBy($campo)
                ->orderBy($campo)
                ->get()
                ->map(fn ($fila) => [
                    'valor' => (string) $fila->valor,
                    'texto' => $this->textoOpcion($campo, (string) $fila->valor, $sockets->all()),
                    'total' => (int) $fila->total,
                ])
                ->all();

            if (count($opciones) > 1 || isset($this->tecnicos[$campo])) {
                $resultado[$campo] = ['etiqueta' => $etiqueta, 'opciones' => $opciones];
            }
        }

        return $resultado;
    }

    /**
     * @param  array<int, string>  $sockets
     */
    private function textoOpcion(string $campo, string $valor, array $sockets): string
    {
        return match (true) {
            $campo === 'socket_id' => $sockets[(int) $valor] ?? $valor,
            str_ends_with($campo, '_gb') => (int) $valor >= 1000 ? ((int) $valor / 1000).' TB' : $valor.' GB',
            $campo === 'potencia_w' => $valor.' W',
            $campo === 'frecuencia_hz' => $valor.' Hz',
            default => $valor,
        };
    }

    /**
     * @param  Builder<Producto>  $consulta
     */
    private function ordenar(Builder $consulta): void
    {
        match ($this->orden) {
            'precio_asc' => $consulta->reorder()->orderBy('precio'),
            'precio_desc' => $consulta->reorder()->orderByDesc('precio'),
            'nuevos' => $consulta->reorder()->latest('id'),
            default => trim($this->q) === ''
                ? $consulta->orderByDesc('destacado')->orderByRaw('existencia > reservado DESC')->orderBy('nombre')
                : null,
        };
    }
}
