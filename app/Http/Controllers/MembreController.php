<?php

namespace App\Http\Controllers;

use App\Models\Localite;
use App\Models\Membre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class MembreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Membre::with(['cotisations', 'depensesMedicales', 'utilisateur', 'localite']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($request->has('localite_id') && $request->localite_id) {
            $query->where('localite_id', $request->localite_id);
        }

        $membres = $query->latest('date_adhesion')->paginate(15)->withQueryString();

        // Ajouter les attributs calculés
        $membres->getCollection()->transform(function ($membre) {
            $membre->total_cotisations = $membre->total_cotisations ?? 0;
            $membre->total_depenses = $membre->depensesMedicales->sum(function ($depense) {
                return $depense->montant_total ?? $depense->montant ?? 0;
            });
            $membre->total_depenses_medicales = $membre->total_depenses; // Alias pour compatibilité
            $membre->statut_text = ($membre->statut ?? 0) === 0 ? 'Actif' : 'Inactif';
            return $membre;
        });

        return Inertia::render('Membres/Index', [
            'membres' => $membres,
            'localites' => Localite::orderBy('libelle')->get(['id', 'libelle']),
            'filters' => $request->only(['search', 'localite_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Membres/Create', [
            'localites' => Localite::orderBy('libelle')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'utilisateur_id' => 'nullable|exists:utilisateurs,id',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'localite_id' => 'nullable|exists:localite,id',
            'adresse' => 'nullable|string',
            'date_adhesion' => 'nullable|date',
            'statut' => 'nullable|integer|in:0,1', // 0 = actif, 1 = inactif
            'est_utilisateur' => 'nullable|boolean',
            'email' => 'required_if:est_utilisateur,true|nullable|email|max:150|unique:utilisateurs,email',
            'password' => ['required_if:est_utilisateur,true', 'nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        // Valeur par défaut si statut n'est pas fourni
        if (!isset($validated['statut'])) {
            $validated['statut'] = 0; // Actif par défaut
        }

        // Créer l'utilisateur si demandé
        $utilisateurId = $validated['utilisateur_id'] ?? null;
        
        if ($request->boolean('est_utilisateur')) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role_id' => null,
                'est_bloque' => false,
            ]);
            $utilisateurId = $user->id;
        }

        // Créer le membre
        $membreData = [
            'utilisateur_id' => $utilisateurId,
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'telephone' => $validated['telephone'] ?? null,
            'localite_id' => $validated['localite_id'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'date_adhesion' => $validated['date_adhesion'] ?? null,
            'statut' => $validated['statut'],
        ];

        Membre::create($membreData);

        return redirect()->route('membres.index')
            ->with('success', 'Membre créé avec succès' . ($request->boolean('est_utilisateur') ? ' ainsi que son compte utilisateur' : '') . '.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Membre $membre): Response
    {
        $membre->load([
            'cotisations' => fn($q) => $q->with(['modePaiement', 'statutCotisation'])->latest()->take(10),
            'depensesMedicales' => fn($q) => $q->with(['categorieDepense', 'attachments'])->latest()->take(10),
        ]);

        return Inertia::render('Membres/Show', [
            'membre' => $membre,
            'totalCotisations' => $membre->total_cotisations,
            'totalDepenses' => $membre->total_depenses,
            'isUpToDate' => $membre->isUpToDate(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Membre $membre): Response
    {
        return Inertia::render('Membres/Edit', [
            'membre' => $membre->load(['localite', 'utilisateur']),
            'localites' => Localite::orderBy('libelle')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Membre $membre)
    {
        $rules = [
            'utilisateur_id' => 'nullable|exists:utilisateurs,id',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'localite_id' => 'nullable|exists:localite,id',
            'adresse' => 'nullable|string',
            'date_adhesion' => 'nullable|date',
            'statut' => 'nullable|integer|in:0,1', // 0 = actif, 1 = inactif
            'est_utilisateur' => 'nullable|boolean',
            'email' => 'required_if:est_utilisateur,true|nullable|email|max:150|unique:utilisateurs,email,' . ($membre->utilisateur_id ?? 'NULL') . ',id',
            'modifier_mot_de_passe' => 'nullable|boolean',
        ];

        // Règle pour le mot de passe : requis si création de compte ou si modification demandée
        if ($request->boolean('est_utilisateur')) {
            // Si le membre n'a pas de compte OU si on veut modifier le mot de passe, le mot de passe est requis
            if (!$membre->utilisateur_id || $request->boolean('modifier_mot_de_passe')) {
                $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
            } else {
                $rules['password'] = ['nullable'];
            }
        } else {
            $rules['password'] = ['nullable'];
        }

        $validated = $request->validate($rules);

        // Mettre à jour les données du membre
        $membreData = [
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'telephone' => $validated['telephone'] ?? null,
            'localite_id' => $validated['localite_id'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'date_adhesion' => $validated['date_adhesion'] ?? null,
            'statut' => $validated['statut'] ?? 0,
        ];

        // Gérer le compte utilisateur
        $compteModifie = false;
        $compteCree = false;
        
        if ($request->boolean('est_utilisateur')) {
            if ($membre->utilisateur_id) {
                // Mettre à jour l'utilisateur existant
                $user = User::find($membre->utilisateur_id);
                if ($user) {
                    $user->email = $validated['email'];
                    // Mettre à jour le mot de passe seulement si demandé
                    if ($request->boolean('modifier_mot_de_passe') && !empty($validated['password'])) {
                        $user->password = $validated['password'];
                    }
                    $user->save();
                    $compteModifie = true;
                }
            } else {
                // Créer un nouvel utilisateur
                if (empty($validated['password'])) {
                    return redirect()->back()
                        ->withErrors(['password' => 'Le mot de passe est obligatoire pour créer un compte utilisateur.'])
                        ->withInput();
                }
                $user = User::create([
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'role_id' => null,
                    'est_bloque' => false,
                ]);
                $membreData['utilisateur_id'] = $user->id;
                $compteCree = true;
            }
        }
        // Si on décoche est_utilisateur, on ne modifie pas le compte existant

        $membre->update($membreData);

        $message = 'Membre mis à jour avec succès.';
        if ($compteCree) {
            $message .= ' Compte utilisateur créé.';
        } elseif ($compteModifie) {
            $message .= ' Compte utilisateur mis à jour.';
        }

        return redirect()->route('membres.show', $membre)
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Membre $membre)
    {
        $membre->delete();

        return redirect()->route('membres.index')
            ->with('success', 'Membre supprimé avec succès.');
    }

    /**
     * Print report of members
     */
    public function print(Request $request): View
    {
        $query = Membre::with(['localite']);

        $filters = [];
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
            $filters['search'] = $request->search;
        }

        if ($request->has('localite_id') && $request->localite_id) {
            $query->where('localite_id', $request->localite_id);
            $filters['localite'] = Localite::find($request->localite_id);
        }

        $membres = $query->orderBy('prenom')->get();

        // Grouper les membres par localité
        $membresParLocalite = $membres->groupBy(function ($membre) {
            return $membre->localite ? $membre->localite->libelle : 'Sans localité';
        });

        // Convertir en structure simple sans totaux
        $membresParLocalite = $membresParLocalite->map(function ($membresLocalite) {
            return [
                'membres' => $membresLocalite,
            ];
        });

        $titre = 'LISTE DES MEMBRES ZIKOBOUÉ POUR LA VIE';

        return view('membres.print', [
            'membresParLocalite' => $membresParLocalite,
            'filters' => $filters,
            'titre' => $titre,
            'localiteSelectionnee' => isset($filters['localite']),
        ]);
    }
}
