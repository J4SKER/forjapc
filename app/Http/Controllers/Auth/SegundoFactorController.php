<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SegundoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Los clientes no usan segundo factor: vuelven a su cuenta sin generar códigos.
        if (! $this->segundoFactor->requerido($usuario)) {
            return redirect()->route('dashboard');
        }

        if ($this->segundoFactor->verificado($request, $usuario)) {
            return redirect()->intended(route('admin.inicio'));
        }

        if (! $this->segundoFactor->hayCodigoVigente($usuario)) {
            $this->segundoFactor->enviarCodigo($usuario);
        }

        return view('auth.dos-pasos', ['correo' => $this->ocultar($usuario->email)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($this->segundoFactor->requerido($usuario), 404);

        $datos = $request->validate(['codigo' => ['required', 'string']], [], ['codigo' => 'código']);
        $error = $this->segundoFactor->verificar($request, $usuario, preg_replace('/\D/', '', $datos['codigo']));

        if ($error === SegundoFactorService::BLOQUEADA) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Bloqueamos tu cuenta por una hora por demasiados códigos incorrectos. Si no fuiste tú, cambia tu contraseña.']);
        }

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
        abort_unless($this->segundoFactor->requerido($request->user()), 404);

        $this->segundoFactor->enviarCodigo($request->user());

        return back()->with('estado', 'Te enviamos un código nuevo. El anterior ya no sirve.');
    }

    private function ocultar(string $correo): string
    {
        [$nombre, $dominio] = explode('@', $correo, 2);

        return Str::substr($nombre, 0, 2).str_repeat('•', max(1, Str::length($nombre) - 2)).'@'.$dominio;
    }
}
