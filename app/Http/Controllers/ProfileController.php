<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Notifications\CuentaModificada;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $correoAnterior = $usuario->email;
        $usuario->fill($request->safe()->except('password_actual'));

        if ($usuario->isDirty('email')) {
            $usuario->email_verified_at = null;
        }

        $usuario->save();

        if ($correoAnterior !== $usuario->email) {
            app(BitacoraService::class)->registrar('correo_cambiado', $usuario, ['email' => $correoAnterior], ['email' => $usuario->email]);
            $usuario->sendEmailVerificationNotification();
            Notification::route('mail', $correoAnterior)->notify(new CuentaModificada(
                ["El correo de acceso de tu cuenta cambió de {$correoAnterior} a {$usuario->email}."],
                'Alguien con la sesión de tu cuenta iniciada',
            ));
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
