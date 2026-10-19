<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SegundoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pantalla del código de verificación en dos pasos del personal.
 */
class SegundoFactorController extends Controller
{
    public function __construct(private SegundoFactorService $segundoFactor) {}

    public function show(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();

        if (! $this->segundoFactor->requerido($usuario) || $this->segundoFactor->verificado($request, $usuario)) {
            return redirect()->intended(route('admin.inicio'));
        }

        if (! $this->segundoFactor->hayCodigoVigente($usuario)) {
            $this->segundoFactor->enviarCodigo($usuario);
        }

        return view('auth.dos-pasos', ['correo' => $this->ocultar($usuario->email)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate(['codigo' => ['required', 'string']], [], ['codigo' => 'código']);
        $usuario = $request->user();
        $error = $this->segundoFactor->verificar($request, $usuario, preg_replace('/\D/', '', $datos['codigo']));

        if ($error !== null) {
            if (! $this->segundoFactor->hayCodigoVigente($usuario)) {
                $this->segundoFactor->enviarCodigo($usuario);
            }

            throw ValidationException::withMessages(['codigo' => $error]);
        }

        $respuesta = redirect()->intended(route('admin.inicio'));

        return $request->boolean('recordar') ? $respuesta->withCookie($this->segundoFactor->recordarEquipo($usuario)) : $respuesta;
    }

    public function reenviar(Request $request): RedirectResponse
    {
        $this->segundoFactor->enviarCodigo($request->user());

        return back()->with('estado', 'Te enviamos un código nuevo. El anterior ya no sirve.');
    }

    private function ocultar(string $correo): string
    {
        [$nombre, $dominio] = explode('@', $correo, 2);

        return Str::substr($nombre, 0, 2).str_repeat('•', max(1, Str::length($nombre) - 2)).'@'.$dominio;
    }
}
