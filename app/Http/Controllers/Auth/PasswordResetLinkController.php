<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Forcer la locale française pour les messages
        App::setLocale('fr');

        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être une adresse e-mail valide.',
        ]);

        // Vérifier si l'utilisateur existe
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [trans('passwords.user', [], 'fr')],
            ]);
        }

        // Réinitialiser directement le mot de passe avec le mot de passe par défaut
        $defaultPassword = 'zikobouevie';
        
        try {
            // Mettre à jour le mot de passe de l'utilisateur
            $user->password = $defaultPassword;
            $user->save();
            
            // Logger l'action pour traçabilité
            Log::info('Mot de passe réinitialisé pour ' . $request->email . ' avec le mot de passe par défaut.');
            
            return back()->with('status', 'Le mot de passe a été réinitialisé avec succès. Le nouveau mot de passe est : zikobouevie');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation du mot de passe pour ' . $request->email . ': ' . $e->getMessage());
            
            throw ValidationException::withMessages([
                'email' => ['Une erreur est survenue lors de la réinitialisation du mot de passe. Veuillez réessayer.'],
            ]);
        }
    }
}
