<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * Liste des permissions disponibles dans l'application
     */
    private function getAvailablePermissions(): array
    {
        return [
            'membres.view' => 'Voir les membres',
            'membres.create' => 'Créer des membres',
            'membres.edit' => 'Modifier des membres',
            'membres.delete' => 'Supprimer des membres',
            'cotisations.view' => 'Voir les cotisations',
            'cotisations.create' => 'Créer des cotisations',
            'cotisations.edit' => 'Modifier des cotisations',
            'cotisations.delete' => 'Supprimer des cotisations',
            'depenses.view' => 'Voir les dépenses',
            'depenses.create' => 'Créer des dépenses',
            'depenses.edit' => 'Modifier des dépenses',
            'depenses.delete' => 'Supprimer des dépenses',
            'dashboard.view' => 'Voir le tableau de bord',
            'parametres.view' => 'Voir les paramètres',
            'parametres.manage' => 'Gérer les paramètres',
            'utilisateurs.view' => 'Voir les utilisateurs',
            'utilisateurs.create' => 'Créer des utilisateurs',
            'utilisateurs.edit' => 'Modifier les utilisateurs',
            'utilisateurs.delete' => 'Supprimer les utilisateurs',
            'roles.view' => 'Voir les rôles',
            'roles.create' => 'Créer des rôles',
            'roles.edit' => 'Modifier les rôles',
            'roles.delete' => 'Supprimer les rôles',
            'admin' => 'Administrateur (toutes les permissions)',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $roles = Role::withCount('utilisateurs')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Parametres/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Parametres/Roles/Create', [
            'permissions' => $this->getAvailablePermissions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'actif' => 'boolean',
        ]);

        $role = Role::create([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'actif' => $validated['actif'] ?? true,
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Le rôle a été créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): Response
    {
        $role->load('utilisateurs.membre');

        return Inertia::render('Parametres/Roles/Show', [
            'role' => $role,
            'permissions' => $this->getAvailablePermissions(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('Parametres/Roles/Edit', [
            'role' => $role,
            'permissions' => $this->getAvailablePermissions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:roles,nom,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'actif' => 'boolean',
        ]);

        $role->update([
            'nom' => $validated['nom'],
            'slug' => Str::slug($validated['nom']),
            'description' => $validated['description'] ?? null,
            'permissions' => $validated['permissions'] ?? [],
            'actif' => $validated['actif'] ?? true,
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Le rôle a été modifié avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Vérifier si le rôle est utilisé
        if ($role->utilisateurs()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Ce rôle ne peut pas être supprimé car il est assigné à des utilisateurs.');
        }

        // Empêcher la suppression du rôle admin
        if ($role->slug === 'admin') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Le rôle administrateur ne peut pas être supprimé.');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Le rôle a été supprimé avec succès.');
    }
}
