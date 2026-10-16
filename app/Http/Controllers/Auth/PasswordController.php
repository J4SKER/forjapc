<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, BitacoraService $bitacora): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $eraTemporal = $user->debe_cambiar_password;

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'debe_cambiar_password' => false,
        ])->save();

        $bitacora->registrar('password_cambiada', $user);

        if ($eraTemporal) {
            return redirect()->route('dashboard')->with('estado', 'Contraseña actualizada. ¡Bienvenido!');
        }

        return back()->with('status', 'password-updated');
    }
}
