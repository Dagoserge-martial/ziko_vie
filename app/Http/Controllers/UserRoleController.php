<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    /**
     * Afficher la liste des utilisateurs avec leurs rôles
     */
    public function index(): Response
    {
        $users = User::with(['role', 'membre'])
            ->orderBy('email')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role_id' => $user->role_id,
                    'role_nom' => $user->role ? $user->role->nom : 'Aucun rôle',
                    'est_bloque' => $user->est_bloque,
                    'membre_id' => $user->membre ? $user->membre->id : null,
                ];
            });

        $roles = Role::where('actif', true)
            ->orderBy('nom')
            ->get();

        return Inertia::render('Parametres/Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $user->update([
            'role_id' => $validated['role_id'] ?? null,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Le rôle a été assigné avec succès.');
    }

    /**
     * Bloquer/Débloquer un utilisateur
     */
    public function toggleBlock(User $user)
    {
        $user->update([
            'est_bloque' => !$user->est_bloque,
        ]);

        $message = $user->est_bloque 
            ? 'L\'utilisateur a été bloqué avec succès.'
            : 'L\'utilisateur a été débloqué avec succès.';

        return redirect()
            ->route('users.index')
            ->with('success', $message);
    }
}
