<?php

namespace App\Http\Controllers;

use App\Models\CategorieDepense;
use App\Models\DepenseMedicale;
use App\Models\Localite;
use App\Models\Membre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class DepenseMedicaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = DepenseMedicale::with(['membre.localite', 'categorieDepense', 'enregistrePar', 'attachments']);

        if ($request->has('membre_id') && $request->membre_id) {
            $query->where('membre_id', $request->membre_id);
        }

        if ($request->has('localite_id') && $request->localite_id) {
            $query->whereHas('membre', function ($q) use ($request) {
                $q->where('localite_id', $request->localite_id);
            });
        }

        if ($request->has('annee') && $request->annee) {
            $query->whereYear('date_depense', $request->annee);
        }

        if ($request->has('mois') && $request->mois) {
            $query->whereMonth('date_depense', $request->mois);
        }

        $depenses = $query->latest('date_depense')->paginate(20)->withQueryString();

        // Récupérer les membres selon la localité sélectionnée
        $membresQuery = Membre::query();
        if ($request->has('localite_id') && $request->localite_id) {
            $membresQuery->where('localite_id', $request->localite_id);
        }
        $membres = $membresQuery->orderBy('nom')->orderBy('prenom')->get(['id', 'prenom', 'nom', 'localite_id']);

        return Inertia::render('DepensesMedicales/Index', [
            'depenses' => $depenses,
            'membres' => $membres,
            'localites' => Localite::orderBy('libelle')->get(['id', 'libelle']),
            'categories' => CategorieDepense::where('actif', 1)->orderBy('libelle')->get(),
            'filters' => $request->only(['membre_id', 'localite_id', 'annee', 'mois']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('DepensesMedicales/Create', [
            'membres' => Membre::orderBy('nom')->orderBy('prenom')->get(['id', 'prenom', 'nom']),
            'categories' => CategorieDepense::where('actif', 1)->orderBy('libelle')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'categorie_depense_id' => 'required|exists:categories_depenses,id',
            'description' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'nom_prestataire' => 'nullable|string|max:255',
            'nom_delegue' => 'nullable|string|max:255',
            'montant_transport' => 'nullable|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // Mapper les noms de champs vers les colonnes de la base de données
        $montant = (float) $validated['montant'];
        $transport = (float) ($validated['montant_transport'] ?? 0);
        
        $data = [
            'membre_id' => $validated['membre_id'],
            'categorie_depense_id' => $validated['categorie_depense_id'],
            'description' => $validated['description'],
            'montant' => $montant,
            'date_depense' => $validated['date_depense'],
            'nom_prestataire' => $validated['nom_prestataire'] ?? null,
            'personne_deleguee' => $validated['nom_delegue'] ?? null,
            'transport_pers_deleguee' => $validated['montant_transport'] ? (string) $validated['montant_transport'] : null,
            'montant_total' => $montant + $transport,
            'utilisateur_id' => Auth::id(),
        ];

        $attachments = $request->file('attachments', []);
        $depense = DepenseMedicale::create($data);

        // Gérer les pièces jointes
        foreach ($attachments as $file) {
            $path = $file->store('depenses-attachments', 'public');
            $depense->attachments()->create([
                'chemin_fichier' => $path,
                'nom_fichier' => $file->getClientOriginalName(),
                'type_mime' => $file->getMimeType(),
                'taille_fichier' => $file->getSize(),
            ]);
        }

        return redirect()->route('depenses-medicales.index')
            ->with('success', 'Dépense médicale enregistrée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DepenseMedicale $depenseMedicale): Response
    {
        $depenseMedicale->load(['membre', 'categorieDepense', 'enregistrePar', 'attachments']);

        return Inertia::render('DepensesMedicales/Show', [
            'depense' => [
                'id' => $depenseMedicale->id,
                'membre_id' => $depenseMedicale->membre_id,
                'categorie_depense_id' => $depenseMedicale->categorie_depense_id,
                'description' => $depenseMedicale->description,
                'montant' => $depenseMedicale->montant,
                'date_depense' => $depenseMedicale->date_depense,
                'nom_prestataire' => $depenseMedicale->nom_prestataire,
                'personne_deleguee' => $depenseMedicale->personne_deleguee,
                'nom_delegue' => $depenseMedicale->personne_deleguee,
                'transport_pers_deleguee' => $depenseMedicale->transport_pers_deleguee,
                'montant_transport' => $depenseMedicale->transport_pers_deleguee,
                'created_at' => $depenseMedicale->created_at,
                'membre' => $depenseMedicale->membre ? [
                    'id' => $depenseMedicale->membre->id,
                    'prenom' => $depenseMedicale->membre->prenom,
                    'nom' => $depenseMedicale->membre->nom,
                ] : null,
                'categorie_depense' => $depenseMedicale->categorieDepense ? [
                    'id' => $depenseMedicale->categorieDepense->id,
                    'libelle' => $depenseMedicale->categorieDepense->libelle,
                    'nom' => $depenseMedicale->categorieDepense->libelle,
                ] : null,
                'categorieDepense' => $depenseMedicale->categorieDepense ? [
                    'id' => $depenseMedicale->categorieDepense->id,
                    'libelle' => $depenseMedicale->categorieDepense->libelle,
                    'nom' => $depenseMedicale->categorieDepense->libelle,
                ] : null,
                'enregistre_par' => $depenseMedicale->enregistrePar ? [
                    'id' => $depenseMedicale->enregistrePar->id,
                    'name' => $depenseMedicale->enregistrePar->name ?? $depenseMedicale->enregistrePar->email ?? null,
                    'email' => $depenseMedicale->enregistrePar->email ?? null,
                ] : null,
                'enregistrePar' => $depenseMedicale->enregistrePar ? [
                    'id' => $depenseMedicale->enregistrePar->id,
                    'name' => $depenseMedicale->enregistrePar->name ?? $depenseMedicale->enregistrePar->email ?? null,
                    'email' => $depenseMedicale->enregistrePar->email ?? null,
                ] : null,
                'attachments' => $depenseMedicale->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'nom_fichier' => $attachment->nom_fichier,
                        'chemin_fichier' => $attachment->chemin_fichier,
                        'taille_fichier' => $attachment->taille_fichier,
                    ];
                })->toArray(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DepenseMedicale $depenseMedicale): Response
    {
        // Vérifier que le modèle est bien chargé
        if (!$depenseMedicale->exists) {
            abort(404, 'Dépense médicale non trouvée');
        }
        
        // Recharger le modèle depuis la base de données pour s'assurer d'avoir toutes les données
        $depenseMedicale->refresh();
        $depenseMedicale->load(['membre', 'categorieDepense', 'attachments']);
        
        // Debug: vérifier les données
        Log::info('Données de la dépense dans edit:', [
            'id' => $depenseMedicale->id,
            'membre_id' => $depenseMedicale->membre_id,
            'description' => $depenseMedicale->description,
            'montant' => $depenseMedicale->montant,
        ]);
        
        // Préparer les données de la dépense de manière explicite
        $depense = [
            'id' => $depenseMedicale->id,
            'membre_id' => $depenseMedicale->membre_id,
            'categorie_depense_id' => $depenseMedicale->categorie_depense_id,
            'description' => $depenseMedicale->description ?? '',
            'montant' => $depenseMedicale->montant ?? 0,
            'date_depense' => $depenseMedicale->date_depense 
                ? (\Carbon\Carbon::parse($depenseMedicale->date_depense)->format('Y-m-d'))
                : null,
            'nom_prestataire' => $depenseMedicale->nom_prestataire ?? '',
            'personne_deleguee' => $depenseMedicale->personne_deleguee ?? '',
            'nom_delegue' => $depenseMedicale->personne_deleguee ?? '',
            'transport_pers_deleguee' => $depenseMedicale->transport_pers_deleguee ?? '',
            'montant_transport' => $depenseMedicale->transport_pers_deleguee ?? '',
            'attachments' => $depenseMedicale->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'nom_fichier' => $attachment->nom_fichier,
                    'chemin_fichier' => $attachment->chemin_fichier,
                    'taille_fichier' => $attachment->taille_fichier,
                ];
            })->toArray(),
        ];
        
        return Inertia::render('DepensesMedicales/Edit', [
            'depense' => $depense,
            'membres' => Membre::orderBy('nom')->orderBy('prenom')->get(['id', 'prenom', 'nom']),
            'categories' => CategorieDepense::where('actif', 1)->orderBy('libelle')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DepenseMedicale $depenseMedicale)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'categorie_depense_id' => 'required|exists:categories_depenses,id',
            'description' => 'required|string|max:200',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'nom_prestataire' => 'nullable|string|max:50',
            'nom_delegue' => 'nullable|string|max:50',
            'montant_transport' => 'nullable|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // Mapper les noms de champs vers les colonnes de la base de données
        $montant = (float) $validated['montant'];
        $transport = (float) ($validated['montant_transport'] ?? 0);
        
        $data = [
            'membre_id' => $validated['membre_id'],
            'categorie_depense_id' => $validated['categorie_depense_id'],
            'description' => $validated['description'],
            'montant' => $montant,
            'date_depense' => $validated['date_depense'],
            'nom_prestataire' => $validated['nom_prestataire'] ?? null,
            'personne_deleguee' => $validated['nom_delegue'] ?? null,
            'transport_pers_deleguee' => $validated['montant_transport'] ? (string) $validated['montant_transport'] : null,
            'montant_total' => $montant + $transport,
        ];

        $attachments = $request->file('attachments', []);
        $depenseMedicale->update($data);

        // Gérer les nouvelles pièces jointes
        foreach ($attachments as $file) {
            $path = $file->store('depenses-attachments', 'public');
            $depenseMedicale->attachments()->create([
                'chemin_fichier' => $path,
                'nom_fichier' => $file->getClientOriginalName(),
                'type_mime' => $file->getMimeType(),
                'taille_fichier' => $file->getSize(),
            ]);
        }

        return redirect()->route('depenses-medicales.show', $depenseMedicale->id)
            ->with('success', 'Dépense médicale mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DepenseMedicale $depenseMedicale)
    {
        // Supprimer les fichiers associés
        foreach ($depenseMedicale->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->chemin_fichier);
        }

        $depenseMedicale->delete();

        return redirect()->route('depenses-medicales.index')
            ->with('success', 'Dépense médicale supprimée avec succès.');
    }

    /**
     * Print report of medical expenses
     */
    public function print(Request $request): View
    {
        $baseQuery = DepenseMedicale::with(['membre.localite', 'categorieDepense']);

        $filters = [];
        if ($request->has('membre_id') && $request->membre_id) {
            $baseQuery->where('membre_id', $request->membre_id);
            $filters['membre'] = Membre::find($request->membre_id);
        }
        if ($request->has('localite_id') && $request->localite_id) {
            $baseQuery->whereHas('membre', function ($q) use ($request) {
                $q->where('localite_id', $request->localite_id);
            });
            $filters['localite'] = Localite::find($request->localite_id);
        }
        if ($request->has('annee') && $request->annee) {
            $filters['annee'] = $request->annee;
        }
        if ($request->has('mois') && $request->mois) {
            $moisNoms = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
            $filters['mois'] = $moisNoms[$request->mois] ?? $request->mois;
        }

        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $titre = 'DÉPENSES MÉDICALES ZIKOBOUÉ POUR LA VIE';
        $tableauxParMois = [];
        
        // Déterminer les mois à afficher
        if (isset($filters['annee']) && !isset($filters['mois'])) {
            // Seule l'année est sélectionnée : tous les mois de l'année
            $annee = $filters['annee'];
            $moisAAfficher = range(1, 12);
        } elseif (!isset($filters['annee']) && !isset($filters['mois']) || (isset($filters['localite']) && !isset($filters['annee']) && !isset($filters['mois']))) {
            // Aucun filtre ou seule la localité est sélectionnée : mois passés et actuel de l'année en cours
            $annee = date('Y');
            $moisActuel = date('n');
            $moisAAfficher = range(1, $moisActuel);
        } else {
            // Mois spécifique sélectionné ou autres cas
            $annee = $filters['annee'] ?? date('Y');
            $moisAAfficher = isset($request->mois) ? [$request->mois] : [date('n')];
        }
        
        // Pour chaque mois, récupérer les dépenses et les grouper
        foreach ($moisAAfficher as $mois) {
            // Recréer la requête de base pour chaque mois
            $queryMois = DepenseMedicale::with(['membre.localite', 'categorieDepense']);
            
            // Réappliquer les filtres existants
            if ($request->has('membre_id') && $request->membre_id) {
                $queryMois->where('membre_id', $request->membre_id);
            }
            
            if ($request->has('localite_id') && $request->localite_id) {
                $queryMois->whereHas('membre', function ($q) use ($request) {
                    $q->where('localite_id', $request->localite_id);
                });
            }
            
            // Ajouter les filtres année et mois
            $queryMois->whereYear('date_depense', $annee)
                      ->whereMonth('date_depense', $mois);
            
            $depensesMois = $queryMois->latest('date_depense')->get();
            
            if ($depensesMois->isEmpty()) {
                continue; // Passer au mois suivant si aucune dépense
            }
            
            // Grouper les dépenses par localité
            $depensesParLocalite = $depensesMois->groupBy(function ($depense) {
                return $depense->membre->localite ? $depense->membre->localite->libelle : 'Sans localité';
            });
            
            $montantTotal = $depensesMois->sum(function ($depense) {
                return $depense->montant_total ?? $depense->montant;
            });
            
            // Générer le sous-titre selon les filtres
            $sousTitre = '';
            if (isset($filters['localite']) && !isset($filters['annee']) && !isset($filters['mois'])) {
                // Seule la localité est sélectionnée : afficher le mois et l'année avec la localité
                $sousTitre = $moisNoms[$mois] . ' ' . $annee . ' - ' . $filters['localite']->libelle;
            } elseif (isset($filters['annee']) && isset($filters['mois'])) {
                // Année et mois sélectionnés
                if (isset($filters['localite'])) {
                    $sousTitre = $filters['annee'] . ' - ' . $filters['localite']->libelle;
                } else {
                    $sousTitre = $filters['mois'] . ' ' . $filters['annee'];
                }
            } elseif (isset($filters['annee']) && !isset($filters['mois'])) {
                // Seule l'année est sélectionnée : afficher le mois
                if (isset($filters['localite'])) {
                    $sousTitre = $moisNoms[$mois] . ' ' . $filters['annee'] . ' - ' . $filters['localite']->libelle;
                } else {
                    $sousTitre = $moisNoms[$mois] . ' ' . $filters['annee'];
                }
            } elseif (!isset($filters['annee']) && !isset($filters['mois'])) {
                // Aucun filtre : afficher le mois et l'année
                $sousTitre = $moisNoms[$mois] . ' ' . $annee;
            } else {
                $sousTitre = $moisNoms[$mois] . ' ' . $annee;
            }
            
            $tableauxParMois[] = [
                'mois' => $mois,
                'moisNom' => $moisNoms[$mois],
                'annee' => $annee,
                'sousTitre' => $sousTitre,
                'depensesParLocalite' => $depensesParLocalite,
                'montantTotal' => $montantTotal,
                'depenses' => $depensesMois,
            ];
        }
        
        // Si aucun tableau n'a été généré (aucune dépense), créer un tableau vide
        if (empty($tableauxParMois)) {
            $annee = $filters['annee'] ?? date('Y');
            $mois = isset($request->mois) ? $request->mois : date('n');
            $sousTitre = '';
            if (isset($filters['localite']) && !isset($filters['annee']) && !isset($filters['mois'])) {
                $sousTitre = $moisNoms[$mois] . ' ' . $annee . ' - ' . $filters['localite']->libelle;
            } elseif (isset($filters['annee']) && isset($filters['mois'])) {
                if (isset($filters['localite'])) {
                    $sousTitre = $filters['annee'] . ' - ' . $filters['localite']->libelle;
                } else {
                    $sousTitre = $filters['mois'] . ' ' . $filters['annee'];
                }
            } elseif (isset($filters['annee']) && !isset($filters['mois'])) {
                if (isset($filters['localite'])) {
                    $sousTitre = $moisNoms[$mois] . ' ' . $filters['annee'] . ' - ' . $filters['localite']->libelle;
                } else {
                    $sousTitre = $moisNoms[$mois] . ' ' . $filters['annee'];
                }
            } elseif (!isset($filters['annee']) && !isset($filters['mois'])) {
                $sousTitre = $moisNoms[$mois] . ' ' . $annee;
            } else {
                $sousTitre = $moisNoms[$mois] . ' ' . $annee;
            }
            
            $tableauxParMois[] = [
                'mois' => $mois,
                'moisNom' => $moisNoms[$mois],
                'annee' => $annee,
                'sousTitre' => $sousTitre,
                'depensesParLocalite' => collect(),
                'montantTotal' => 0,
                'depenses' => collect(),
            ];
        }

        return view('depenses-medicales.print', [
            'tableauxParMois' => $tableauxParMois,
            'filters' => $filters,
            'titre' => $titre,
        ]);
    }
}
