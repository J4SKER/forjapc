<?php

namespace App\Http\Controllers\Tienda;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ResenaProducto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reseñas de clientes con compra verificada; se publican después de moderarlas.
 */
class ResenaController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $producto = Producto::query()->visibles()->where('slug', $slug)->firstOrFail();
        $usuario = $request->user();

        abort_unless(self::comproElProducto($usuario, $producto), 403, 'Solo los clientes que recibieron este producto pueden opinar.');

        $datos = $request->validate([
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'titulo' => ['required', 'string', 'max:120'],
            'comentario' => ['required', 'string', 'min:10', 'max:2000'],
        ], [], ['calificacion' => 'calificación']);

        $resena = ResenaProducto::query()->where('producto_id', $producto->id)->where('usuario_id', $usuario->id)->first() ?? new ResenaProducto;
        $resena->fill($datos);
        $resena->forceFill(['producto_id' => $producto->id, 'usuario_id' => $usuario->id, 'estado' => 'PENDIENTE', 'compra_verificada' => true])->save();

        return redirect()->to(route('tienda.producto', $producto->slug).'#resenas')
            ->with('estado', '¡Gracias por tu opinión! La publicaremos después de revisarla.');
    }

    public static function comproElProducto(?User $usuario, Producto $producto): bool
    {
        return $usuario !== null && $usuario->pedidos()
            ->where('estado', EstadoPedido::Entregado)
            ->whereHas('items', fn ($q) => $q->where('producto_id', $producto->id))
            ->exists();
    }
}
