<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UsuarioRequest;
use App\Models\BitacoraAuditoria;
use App\Models\User;
use App\Notifications\CuentaModificada;
use App\Services\BitacoraService;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Administración de usuarios: alta de personal, roles, activación y desbloqueo.
 */
class UsuarioController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    public function index(Request $request): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => $this->filtrar($request)->orderBy('nombre')->paginate(15)->withQueryString(),
            'roles' => Rol::cases(),
        ]);
    }

    /**
     * Descarga en CSV (compatible con Excel) los usuarios que cumplen los filtros actuales.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $this->bitacora->registrar('usuarios_exportados', null, null, $request->only(['q', 'rol', 'estado']));

        $consulta = $this->filtrar($request)->orderBy('nombre');

        return response()->streamDownload(function () use ($consulta) {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF"); // BOM para que Excel respete las tildes.
            fputcsv($archivo, ['ID', 'Nombre', 'Correo', 'Teléfono', 'NIT', 'Rol', 'Activo', 'Correo verificado', 'Último acceso', 'Registrado']);

            $consulta->chunk(500, function ($usuarios) use ($archivo) {
                foreach ($usuarios as $usuario) {
                    fputcsv($archivo, array_map(Csv::celda(...), [
                        $usuario->id,
                        $usuario->nombre,
                        $usuario->email,
                        $usuario->telefono,
                        $usuario->nit,
                        $usuario->rol->etiqueta(),
                        $usuario->activo ? 'Sí' : 'No',
                        $usuario->email_verified_at ? 'Sí' : 'No',
                        $usuario->ultimo_acceso?->format('d/m/Y H:i'),
                        $usuario->created_at->format('d/m/Y H:i'),
                    ]));
                }
            });

            fclose($archivo);
        }, 'usuarios-forjapc-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Aplica los filtros de búsqueda, rol y estado del listado.
     *
     * @return Builder<User>
     */
    private function filtrar(Request $request): Builder
    {
        return User::query()
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $termino = '%'.$request->string('q')->trim()->value().'%';
                $query->where(fn (Builder $q) => $q->where('nombre', 'like', $termino)->orWhere('email', 'like', $termino));
            })
            ->when($request->filled('rol'), fn (Builder $query) => $query->where('rol', $request->string('rol')->value()))
            ->when($request->input('estado') === 'inactivos', fn (Builder $query) => $query->where('activo', false))
            ->when($request->input('estado') === 'bloqueados', fn (Builder $query) => $query->where('bloqueado_hasta', '>', now()));
    }

    public function create(): View
    {
        return view('admin.usuarios.form', [
            'usuario' => new User(['rol' => Rol::Vendedor]),
            'roles' => UsuarioRequest::rolesAsignables(request()->user()),
        ]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = new User($request->safe()->only(['nombre', 'email', 'telefono', 'password']));
        $usuario->forceFill([
            'rol' => $request->enum('rol', Rol::class),
            'activo' => $request->boolean('activo', true),
            'email_verified_at' => now(),
            'debe_cambiar_password' => true,
        ])->save();

        $this->bitacora->registrar('usuario_creado', $usuario, null, [
            'email' => $usuario->email,
            'rol' => $usuario->rol->value,
        ]);

        return redirect()->route('admin.usuarios.index')->with('estado', "Usuario {$usuario->email} creado.");
    }

    public function edit(Request $request, User $usuario): View
    {
        $this->asegurarGestion($request, $usuario);

        return view('admin.usuarios.form', [
            'usuario' => $usuario,
            'roles' => UsuarioRequest::rolesAsignables($request->user(), $usuario),
            'direcciones' => $usuario->direcciones()->with('departamento')->orderByDesc('es_principal')->get(),
            'actividad' => BitacoraAuditoria::query()
                ->with('usuario:id,nombre')
                ->where(fn (Builder $q) => $q->where('usuario_id', $usuario->id)
                    ->orWhere(fn (Builder $q) => $q->where('entidad_tipo', 'User')->where('entidad_id', $usuario->id)))
                ->latest('id')
                ->limit(10)
                ->get(),
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
        $correoAnterior = $usuario->email;

        $usuario->fill($request->safe()->only(['nombre', 'email', 'telefono']));
        $usuario->forceFill(['rol' => $nuevoRol, 'activo' => $activo]);

        // Un cliente que pasa a ser personal debe elegir una contraseña con las reglas del
        // personal antes de entrar al panel (la suya pudo ser débil).
        if ($nuevoRol->esPersonal() && ! Rol::from($antes['rol'])->esPersonal()) {
            $usuario->debe_cambiar_password = true;
        }

        $usuario->save();

        $despues = $usuario->only(['nombre', 'email', 'telefono']) + ['rol' => $usuario->rol->value, 'activo' => $usuario->activo];

        if ($antes !== $despues) {
            $this->bitacora->registrar(
                $antes['rol'] !== $despues['rol'] ? 'rol_cambiado' : 'usuario_actualizado',
                $usuario,
                array_diff_assoc($antes, $despues),
                array_diff_assoc($despues, $antes),
            );
            $this->avisarCambios($request, $usuario, $antes, $correoAnterior);
        }

        return redirect()->route('admin.usuarios.index')->with('estado', "Usuario {$usuario->email} actualizado.");
    }

    /**
     * Envía al dueño de la cuenta un enlace para que él mismo elija una contraseña nueva:
     * nadie del personal llega a conocerla.
     */
    public function enviarRestablecimiento(Request $request, User $usuario): RedirectResponse
    {
        $this->asegurarGestion($request, $usuario);

        $estado = Password::sendResetLink(['email' => $usuario->email]);

        if ($estado === Password::RESET_LINK_SENT) {
            $this->bitacora->registrar('restablecimiento_enviado', $usuario, null, ['email' => $usuario->email]);
        }

        return back()->with('estado', $estado === Password::RESET_LINK_SENT
            ? "Enviamos a {$usuario->email} un enlace para restablecer su contraseña."
            : 'No se pudo enviar el enlace ahora (ya se envió uno hace poco). Intenta en unos minutos.');
    }

    /**
     * Solo el propietario modifica cuentas de administrador; nadie modifica la del propietario.
     */
    private function asegurarGestion(Request $request, User $usuario): void
    {
        abort_unless($request->user()->puedeGestionarA($usuario), 403, 'Solo el propietario puede modificar cuentas de administrador.');
    }

    /**
     * Aviso de seguridad al afectado (y a su correo anterior, si cambió) con lo que se modificó.
     *
     * @param  array<string, mixed>  $antes
     */
    private function avisarCambios(Request $request, User $usuario, array $antes, string $correoAnterior): void
    {
        $cambios = [];
        if ($antes['rol'] !== $usuario->rol->value) {
            $cambios[] = 'Tu rol cambió de «'.Rol::from($antes['rol'])->etiqueta().'» a «'.$usuario->rol->etiqueta().'».';
        }
        if ($antes['email'] !== $usuario->email) {
            $cambios[] = "Tu correo de acceso cambió de {$correoAnterior} a {$usuario->email}.";
        }
        if ($antes['activo'] !== $usuario->activo) {
            $cambios[] = $usuario->activo ? 'Tu cuenta fue activada.' : 'Tu cuenta fue desactivada: ya no puedes iniciar sesión.';
        }
        if ($antes['nombre'] !== $usuario->nombre || $antes['telefono'] !== $usuario->telefono) {
            $cambios[] = 'Se actualizaron tu nombre o tu teléfono.';
        }

        $aviso = new CuentaModificada($cambios, $request->user()->nombre.' (administración de ForjaPC)');
        $usuario->notify($aviso);

        if ($correoAnterior !== $usuario->email) {
            Notification::route('mail', $correoAnterior)->notify($aviso);
        }
    }

    public function desbloquear(Request $request, User $usuario): RedirectResponse
    {
        $this->asegurarGestion($request, $usuario);

        $usuario->forceFill(['bloqueado_hasta' => null, 'intentos_fallidos' => 0])->save();

        $this->bitacora->registrar('cuenta_desbloqueada', $usuario);

        return back()->with('estado', "La cuenta {$usuario->email} fue desbloqueada.");
    }
}
