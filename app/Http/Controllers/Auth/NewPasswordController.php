<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Forcer la locale française pour les messages
        App::setLocale('fr');

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ], [
            'token.required' => 'Le jeton de réinitialisation est obligatoire.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être une adresse e-mail valide.',
        ]);

        // Utiliser le mot de passe par défaut "zikobouevie"
        $defaultPassword = 'zikobouevie';
        
        // Réinitialiser le mot de passe avec le mot de passe par défaut
        $status = Password::reset(
            [
                'email' => $request->email,
                'password' => $defaultPassword,
                'password_confirmation' => $defaultPassword,
                'token' => $request->token,
            ],
            function ($user) use ($defaultPassword) {
                // Le modèle User gère automatiquement le hashage via setPasswordAttribute
                $user->password = $defaultPassword;
                $user->remember_token = Str::random(60);
                $user->save();

                event(new PasswordReset($user));
            }
        );

        // Si le mot de passe a été réinitialisé avec succès, rediriger vers la page de connexion
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé avec succès. Le nouveau mot de passe est : zikobouevie');
        }

        // Traduire le message d'erreur en français
        $errorMessage = trans($status, [], 'fr');
        if ($status === Password::INVALID_TOKEN) {
            $errorMessage = trans('passwords.token', [], 'fr');
        } elseif ($status === Password::INVALID_USER) {
            $errorMessage = trans('passwords.user', [], 'fr');
        }

        throw ValidationException::withMessages([
            'email' => [$errorMessage],
        ]);
    }
}
