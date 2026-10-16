<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UsuarioRequest;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Administración de usuarios: alta de personal, roles, activación y desbloqueo.
 */
class UsuarioController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    public function index(Request $request): View
    {
        $usuarios = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $termino = '%'.$request->string('q')->trim()->value().'%';
                $query->where(fn ($q) => $q->where('nombre', 'like', $termino)->orWhere('email', 'like', $termino));
            })
            ->when($request->filled('rol'), fn ($query) => $query->where('rol', $request->string('rol')->value()))
            ->when($request->input('estado') === 'inactivos', fn ($query) => $query->where('activo', false))
            ->when($request->input('estado') === 'bloqueados', fn ($query) => $query->where('bloqueado_hasta', '>', now()))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => Rol::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', [
            'usuario' => new User(['rol' => Rol::Vendedor]),
            'roles' => Rol::cases(),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = new User($request->safe()->only(['nombre', 'email', 'telefono', 'password']));
        $usuario->forceFill([
            'rol' => $request->enum('rol', Rol::class),
            'activo' => $request->boolean('activo', true),
            'email_verified_at' => now(),
        ])->save();

        $this->bitacora->registrar('usuario_creado', $usuario, null, [
            'email' => $usuario->email,
            'rol' => $usuario->rol->value,
        ]);

        return redirect()->route('admin.usuarios.index')->with('estado', "Usuario {$usuario->email} creado.");
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', [
            'usuario' => $usuario,
            'roles' => Rol::cases(),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $nuevoRol = $request->enum('rol', Rol::class);
        $activo = $request->boolean('activo');

        if ($usuario->is($request->user()) && ($nuevoRol !== Rol::Admin || ! $activo)) {
            throw ValidationException::withMessages([
                'rol' => 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.',
            ]);
        }

        $antes = $usuario->only(['nombre', 'email', 'telefono']) + ['rol' => $usuario->rol->value, 'activo' => $usuario->activo];

        $usuario->fill($request->safe()->only(['nombre', 'email', 'telefono']));
        if ($request->filled('password')) {
            $usuario->password = $request->string('password')->value();
        }
        $usuario->forceFill(['rol' => $nuevoRol, 'activo' => $activo])->save();

        $despues = $usuario->only(['nombre', 'email', 'telefono']) + ['rol' => $usuario->rol->value, 'activo' => $usuario->activo];

        if ($antes !== $despues || $request->filled('password')) {
            $this->bitacora->registrar(
                $antes['rol'] !== $despues['rol'] ? 'rol_cambiado' : 'usuario_actualizado',
                $usuario,
                array_diff_assoc($antes, $despues),
                array_diff_assoc($despues, $antes) + ($request->filled('password') ? ['password' => '(cambiada)'] : []),
            );
        }

        return redirect()->route('admin.usuarios.index')->with('estado', "Usuario {$usuario->email} actualizado.");
    }

    public function desbloquear(User $usuario): RedirectResponse
    {
        $usuario->forceFill(['bloqueado_hasta' => null, 'intentos_fallidos' => 0])->save();

        $this->bitacora->registrar('cuenta_desbloqueada', $usuario);

        return back()->with('estado', "La cuenta {$usuario->email} fue desbloqueada.");
    }
}
