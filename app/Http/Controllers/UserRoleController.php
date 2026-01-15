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
        $users = User::with(['roles', 'role', 'membre'])
            ->orderBy('email')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role_ids' => $user->roles->pluck('id')->toArray(),
                    'role_id' => $user->role_id, // Pour compatibilité
                    'role_nom' => $user->roles->pluck('nom')->join(', ') ?: ($user->role ? $user->role->nom : 'Aucun rôle'),
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
     * Assigner des rôles à un utilisateur
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $roleIds = $validated['role_ids'] ?? [];
        
        // Synchroniser les rôles (many-to-many)
        $user->roles()->sync($roleIds);

        return redirect()
            ->route('parametres.users.index')
            ->with('success', 'Les rôles ont été assignés avec succès.');
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
            ->route('parametres.users.index')
            ->with('success', $message);
    }
}
