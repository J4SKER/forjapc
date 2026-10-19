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
 * con vencimiento y límite de intentos. El equipo se puede recordar durante unos días con una
 * cookie cifrada que deja de valer si la contraseña cambia.
 */
class SegundoFactorService
{
    public const SESION = 'dos_pasos_verificado';

    public const COOKIE = 'forjapc_equipo';

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
        Cache::put($this->clave($usuario), ['hash' => Hash::make($codigo), 'intentos' => 0], now()->addMinutes((int) config('forjapc.dos_pasos.minutos_validez')));

        $usuario->notify(new CodigoAcceso($codigo, (int) config('forjapc.dos_pasos.minutos_validez')));
    }

    public function hayCodigoVigente(User $usuario): bool
    {
        return Cache::has($this->clave($usuario));
    }

    /**
     * Comprueba el código. Devuelve null si es correcto o el mensaje de error.
     */
    public function verificar(Request $request, User $usuario, string $codigo): ?string
    {
        $datos = Cache::get($this->clave($usuario));

        if ($datos === null) {
            return 'El código venció. Te enviamos uno nuevo.';
        }

        if (! Hash::check($codigo, $datos['hash'])) {
            $datos['intentos']++;
            $this->bitacora->registrar('segundo_factor_fallido', $usuario, null, ['intento' => $datos['intentos']]);

            if ($datos['intentos'] >= (int) config('forjapc.dos_pasos.intentos_maximos')) {
                Cache::forget($this->clave($usuario));

                return 'Demasiados intentos. Te enviamos un código nuevo.';
            }

            Cache::put($this->clave($usuario), $datos, now()->addMinutes((int) config('forjapc.dos_pasos.minutos_validez')));

            return 'El código no es correcto.';
        }

        Cache::forget($this->clave($usuario));
        $request->session()->put(self::SESION, $usuario->id);
        $this->bitacora->registrar('segundo_factor_superado', $usuario);

        return null;
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
}
