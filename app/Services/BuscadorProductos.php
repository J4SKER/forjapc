<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Búsqueda de productos por texto con tolerancia a errores de escritura:
 * combina el índice FULLTEXT de MySQL con coincidencias parciales por palabra y,
 * si no hay resultados, propone la palabra más parecida del catálogo.
 */
class BuscadorProductos
{
    public const CACHE_VOCABULARIO = 'buscador_vocabulario';

    /**
     * Aplica la búsqueda a una consulta de productos y la ordena por relevancia.
     *
     * @param  Builder<Producto>  $consulta
     */
    public function aplicar(Builder $consulta, string $termino): void
    {
        $palabras = $this->palabras($termino);

        if ($palabras === []) {
            return;
        }

        // En modo booleano "-" y "." son operadores: se separan y se ignoran fragmentos muy cortos.
        $booleano = collect($palabras)
            ->flatMap(fn (string $p) => preg_split('/[\-\.]+/', $p))
            ->filter(fn (string $p) => mb_strlen($p) >= 3)
            ->map(fn (string $p) => '+'.$p.'*')
            ->join(' ');

        $consulta->where(function (Builder $q) use ($palabras, $booleano) {
            if ($booleano !== '') {
                $q->whereFullText(['nombre', 'descripcion'], $booleano, ['mode' => 'boolean']);
            }

            $q->orWhere(function (Builder $q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $q->where(fn (Builder $q) => $q->where('nombre', 'like', "%{$palabra}%")
                        ->orWhere('sku', 'like', "%{$palabra}%")
                        ->orWhereHas('marca', fn (Builder $m) => $m->where('nombre', 'like', "%{$palabra}%")));
                }
            });
        });

        if ($booleano !== '') {
            $consulta->orderByRaw('MATCH(nombre, descripcion) AGAINST (? IN BOOLEAN MODE) DESC', [$booleano]);
        }
    }

    /**
     * Propone una búsqueda corregida reemplazando cada palabra desconocida por la más parecida.
     */
    public function sugerencia(string $termino): ?string
    {
        $palabras = $this->palabras($termino);
        $vocabulario = $this->vocabulario();
        $corregidas = [];
        $huboCambio = false;

        foreach ($palabras as $palabra) {
            if (in_array($palabra, $vocabulario, true)) {
                $corregidas[] = $palabra;

                continue;
            }

            $mejor = null;
            $menorDistancia = PHP_INT_MAX;
            foreach ($vocabulario as $candidata) {
                $distancia = levenshtein($palabra, $candidata);
                if ($distancia < $menorDistancia) {
                    [$mejor, $menorDistancia] = [$candidata, $distancia];
                }
            }

            $tolerancia = max(1, intdiv(mb_strlen($palabra), 3));
            if ($mejor !== null && $menorDistancia <= $tolerancia) {
                $corregidas[] = $mejor;
                $huboCambio = true;
            } else {
                $corregidas[] = $palabra;
            }
        }

        return $huboCambio ? implode(' ', $corregidas) : null;
    }

    /**
     * @return list<string>
     */
    private function palabras(string $termino): array
    {
        $limpio = Str::of($termino)->lower()->ascii()->replaceMatches('/[^a-z0-9\-\.]+/', ' ')->squish()->value();

        return array_values(array_filter(explode(' ', $limpio), fn (string $p) => mb_strlen($p) >= 2));
    }

    /**
     * Palabras de nombres y marcas de los productos visibles (en caché).
     *
     * @return list<string>
     */
    private function vocabulario(): array
    {
        return Cache::remember(self::CACHE_VOCABULARIO, now()->addHour(), function () {
            return Producto::query()->visibles()->with('marca:id,nombre')->get(['id', 'nombre', 'marca_id'])
                ->flatMap(fn (Producto $p) => $this->palabras($p->nombre.' '.$p->marca->nombre))
                ->unique()
                ->values()
                ->all();
        });
    }
}
