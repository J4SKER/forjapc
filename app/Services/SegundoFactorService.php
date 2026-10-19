<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CodigoAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie as CookieRespuesta;

/**
 * Segundo factor del personal: un código de 6 dígitos enviado al correo, de un solo uso,
 * con vencimiento fijo y límite de intentos. Si se acumulan demasiados fallos en una hora
 * (aunque se pidan códigos nuevos) la cuenta se bloquea. El equipo se puede recordar unos días
 * con una cookie cifrada que deja de valer si la contraseña cambia.
 */
class SegundoFactorService
{
    public const SESION = 'dos_pasos_verificado';

    public const COOKIE = 'forjapc_equipo';

    /** Fallos permitidos en una hora, sumando todos los códigos, antes de bloquear la cuenta. */
    public const FALLOS_POR_HORA = 10;

    public const BLOQUEADA = 'bloqueada';

    public function __construct(private BitacoraService $bitacora) {}

    public function requerido(User $usuario): bool
    {
        return (bool) config('forjapc.dos_pasos.activo') && $usuario->esPersonal();
    }

    public function verificado(Request $request, User $usuario): bool
    {
        return $request->session()->get(self::SESION) === $usuario->id || $this->equipoRecordado($request, $usuario);
    }

    public function enviarCodigo(User $usuario): void
    {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutos = (int) config('forjapc.dos_pasos.minutos_validez');
        Cache::put($this->clave($usuario), ['hash' => Hash::make($codigo), 'intentos' => 0, 'vence' => now()->addMinutes($minutos)->timestamp], now()->addMinutes($minutos));

        $usuario->notify(new CodigoAcceso($codigo, $minutos));
    }

    public function hayCodigoVigente(User $usuario): bool
    {
        return Cache::has($this->clave($usuario));
    }

    /**
     * Comprueba el código. Devuelve null si es correcto, el mensaje de error o BLOQUEADA si la
     * cuenta se bloqueó por exceso de fallos.
     */
    public function verificar(Request $request, User $usuario, string $codigo): ?string
    {
        $datos = Cache::get($this->clave($usuario));

        if ($datos === null) {
            return 'El código venció. Te enviamos uno nuevo.';
        }

        if (! Hash::check($codigo, $datos['hash'])) {
            return $this->registrarFallo($usuario, $datos);
        }

        Cache::forget($this->clave($usuario));
        Cache::forget($this->claveFallos($usuario));
        $request->session()->put(self::SESION, $usuario->id);
        $this->bitacora->registrar('segundo_factor_superado', $usuario);

        return null;
    }

    /**
     * @param  array{hash: string, intentos: int, vence: int}  $datos
     */
    private function registrarFallo(User $usuario, array $datos): string
    {
        $fallosHora = (int) Cache::get($this->claveFallos($usuario), 0) + 1;
        Cache::put($this->claveFallos($usuario), $fallosHora, now()->addHour());
        $this->bitacora->registrar('segundo_factor_fallido', $usuario, null, ['fallos_en_la_hora' => $fallosHora]);

        if ($fallosHora >= self::FALLOS_POR_HORA) {
            Cache::forget($this->clave($usuario));
            Cache::forget($this->claveFallos($usuario));
            $usuario->forceFill(['bloqueado_hasta' => now()->addHour()])->save();
            $this->bitacora->registrar('cuenta_bloqueada', $usuario, null, ['motivo' => 'Demasiados códigos de verificación incorrectos']);

            return self::BLOQUEADA;
        }

        $datos['intentos']++;

        if ($datos['intentos'] >= (int) config('forjapc.dos_pasos.intentos_maximos')) {
            Cache::forget($this->clave($usuario));

            return 'Demasiados intentos. Te enviamos un código nuevo.';
        }

        // El vencimiento es fijo: un intento fallido no le da más tiempo al código.
        Cache::put($this->clave($usuario), $datos, max(1, $datos['vence'] - now()->timestamp));

        return 'El código no es correcto.';
    }

    /**
     * Cookie cifrada que recuerda este equipo; incluye una huella de la contraseña actual.
     */
    public function recordarEquipo(User $usuario): CookieRespuesta
    {
        $dias = (int) config('forjapc.dos_pasos.dias_recordar_equipo');
        $valor = json_encode(['u' => $usuario->id, 'h' => $this->huella($usuario), 'v' => now()->addDays($dias)->timestamp]);

        return Cookie::make(self::COOKIE, $valor, $dias * 24 * 60, secure: app()->isProduction(), httpOnly: true, sameSite: 'lax');
    }

    private function equipoRecordado(Request $request, User $usuario): bool
    {
        $datos = json_decode((string) $request->cookie(self::COOKIE), true);

        return is_array($datos)
            && ($datos['u'] ?? null) === $usuario->id
            && hash_equals($this->huella($usuario), (string) ($datos['h'] ?? ''))
            && (int) ($datos['v'] ?? 0) > now()->timestamp;
    }

    private function huella(User $usuario): string
    {
        return hash_hmac('sha256', $usuario->id.'|'.$usuario->getAuthPassword(), (string) config('app.key'));
    }

    private function clave(User $usuario): string
    {
        return "dos_pasos:{$usuario->id}";
    }

    private function claveFallos(User $usuario): string
    {
        return "dos_pasos_fallos:{$usuario->id}";
    }
}
