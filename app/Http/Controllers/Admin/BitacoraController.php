<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date']]);

        $registros = BitacoraAuditoria::query()
            ->with('usuario:id,nombre,email')
            ->when($request->filled('accion'), fn ($query) => $query->where('accion', $request->string('accion')->value()))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('hasta')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bitacora.index', [
            'registros' => $registros,
            'acciones' => BitacoraAuditoria::query()->distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }
}
