<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Services\PagoService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verificación de transferencias y listado de pagos.
 */
class PagoController extends Controller
{
    public function index(Request $request): View
    {
        $estado = EstadoPago::tryFrom((string) $request->input('estado', 'PENDIENTE'));

        return view('admin.pagos.index', [
            'pagos' => Pago::query()->with('pedido.usuario:id,nombre,email', 'verificador:id,nombre')
                ->when($estado, fn (Builder $q) => $q->where('estado', $estado))
                ->when($estado === EstadoPago::Pendiente, fn (Builder $q) => $q->where('metodo', MetodoPago::Transferencia)->whereNotNull('comprobante_ruta'))
                ->latest('id')->paginate(20)->withQueryString(),
            'estado' => $estado,
            'porVerificar' => Pago::query()->where('estado', EstadoPago::Pendiente)->where('metodo', MetodoPago::Transferencia)->whereNotNull('comprobante_ruta')->count(),
        ]);
    }

    public function aprobar(Request $request, Pago $pago, PagoService $pagos): RedirectResponse
    {
        try {
            $pagos->aprobar($pago, $request->user());
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return back()->with('estado', "Pago del pedido {$pago->pedido->numero} aprobado. El inventario y el pedido se actualizaron.");
    }

    public function rechazar(Request $request, Pago $pago, PagoService $pagos): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']], [], ['motivo' => 'motivo del rechazo']);

        try {
            $pagos->rechazar($pago, $datos['motivo'], $request->user());
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return back()->with('estado', 'Pago rechazado; avisamos al cliente por correo.');
    }
}
