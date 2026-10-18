<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use App\Support\ReglasPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, BitacoraService $bitacora): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', ReglasPassword::para($request->user()), 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $eraTemporal = $user->debe_cambiar_password;

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'debe_cambiar_password' => false,
        ])->save();

        // Las demás sesiones abiertas quedan cerradas (AuthenticateSession compara este hash);
        // esta sesión se actualiza para seguir activa.
        $request->session()->put('password_hash_web', $user->getAuthPassword());
        $request->session()->regenerate();

        $bitacora->registrar('password_cambiada', $user);

        if ($eraTemporal) {
            return redirect()->route('dashboard')->with('estado', 'Contraseña actualizada. ¡Bienvenido!');
        }

        return back()->with('status', 'password-updated');
    }
}
