<?php

namespace App\Http\Requests\Auth;

use App\Models\ParametroSistema;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Además del límite por IP, la cuenta se bloquea temporalmente después de
     * varios intentos fallidos y las cuentas desactivadas no pueden ingresar.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $user = User::query()->where('email', Str::lower($this->string('email')))->first();

        // El bloqueo de la cuenta se informa antes que el límite por correo e IP.
        if ($user?->estaBloqueadoTemporalmente()) {
            throw ValidationException::withMessages([
                'email' => __('auth.cuenta_bloqueada', [
                    'minutes' => (int) ceil(now()->diffInSeconds($user->bloqueado_hasta) / 60),
                ]),
            ]);
        }

        $this->ensureIsNotRateLimited();

        if ($user === null || ! Auth::validate($this->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey());

            if ($user !== null) {
                $this->registrarIntentoFallido($user);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'email' => __('auth.cuenta_inactiva'),
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        $user->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_acceso' => now(),
        ])->save();

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Cuenta el intento fallido y bloquea la cuenta al llegar al máximo permitido.
     */
    private function registrarIntentoFallido(User $user): void
    {
        $intentos = $user->intentos_fallidos + 1;

        if ($intentos < ParametroSistema::valor('intentos_maximos_login', 5)) {
            $user->forceFill(['intentos_fallidos' => $intentos])->save();

            return;
        }

        $user->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => now()->addMinutes(ParametroSistema::valor('minutos_bloqueo_cuenta', 15)),
        ])->save();

        app(BitacoraService::class)->registrar('cuenta_bloqueada', $user, null, [
            'bloqueado_hasta' => $user->bloqueado_hasta->toDateTimeString(),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
