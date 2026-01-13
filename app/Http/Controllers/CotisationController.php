<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\Localite;
use App\Models\Membre;
use App\Models\ModePaiement;
use App\Models\StatutCotisation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\View\View;

class CotisationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Cotisation::with(['membre.localite', 'modePaiement', 'statutCotisation', 'enregistrePar']);

        if ($request->has('membre_id') && $request->membre_id) {
            $query->where('membre_id', $request->membre_id);
        }

        if ($request->has('localite_id') && $request->localite_id) {
            $query->whereHas('membre', function ($q) use ($request) {
                $q->where('localite_id', $request->localite_id);
            });
        }

        if ($request->has('annee') && $request->annee) {
            $query->where('annee', $request->annee);
        }

        if ($request->has('mois') && $request->mois) {
            $query->where('mois', $request->mois);
        }

        $cotisations = $query->latest('date_paiement')->paginate(20)->withQueryString();

        // Filtrer les membres selon la localité sélectionnée
        $membresQuery = Membre::query();
        if ($request->has('localite_id') && $request->localite_id) {
            $membresQuery->where('localite_id', $request->localite_id);
        }
        $membres = $membresQuery->orderBy('prenom')->get(['id', 'prenom', 'nom']);

        return Inertia::render('Cotisations/Index', [
            'cotisations' => $cotisations,
            'membres' => $membres,
            'localites' => Localite::orderBy('libelle')->get(['id', 'libelle']),
            'modesPaiement' => ModePaiement::where('actif', 1)->orderBy('libelle')->get(),
            'statutsCotisation' => StatutCotisation::orderBy('libelle')->get(),
            'filters' => $request->only(['membre_id', 'localite_id', 'annee', 'mois']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $filtre = $request->input('filtre', 'non_payes'); // Par défaut, ceux qui n'ont pas payé
        $annee = $request->input('annee', date('Y'));
        $mois = $request->input('mois', date('m'));
        
        // Récupérer les IDs des membres qui ont déjà payé pour cette année/mois
        $membresPayesIds = Cotisation::where('annee', $annee)
            ->where('mois', $mois)
            ->pluck('membre_id')
            ->toArray();
        
        // Filtrer les membres selon le filtre sélectionné
        $query = Membre::query();
        
        // Filtrer par localité si sélectionnée
        if ($request->has('localite_id') && $request->localite_id) {
            $query->where('localite_id', $request->localite_id);
        }
        
        if ($filtre === 'payes') {
            // Seulement les membres qui ont payé
            $query->whereIn('id', $membresPayesIds);
        } elseif ($filtre === 'non_payes') {
            // Seulement les membres qui n'ont pas payé
            $query->whereNotIn('id', $membresPayesIds);
        }
        // Si 'tous', pas de filtre supplémentaire
        
        // Paginer les membres
        $membres = $query->orderBy('prenom')
            ->paginate(20, ['id', 'prenom', 'nom', 'telephone'])
            ->withQueryString();
        
        // Récupérer les cotisations existantes pour cette année/mois avec montants
        // Pour tous les membres (pas seulement ceux de la page actuelle)
        $cotisationsExistantes = Cotisation::where('annee', $annee)
            ->where('mois', $mois)
            ->get(['membre_id', 'montant'])
            ->mapWithKeys(function ($cotisation) {
                return [$cotisation->membre_id => ['montant' => $cotisation->montant]];
            })
            ->toArray();

        return Inertia::render('Cotisations/Create', [
            'membres' => $membres,
            'localites' => Localite::orderBy('libelle')->get(['id', 'libelle']),
            'cotisationsExistantes' => $cotisationsExistantes,
            'modesPaiement' => ModePaiement::where('actif', 1)->orderBy('libelle')->get(),
            'statutsCotisation' => StatutCotisation::orderBy('libelle')->get(),
            'filtre' => $filtre,
            'localite_id' => $request->input('localite_id'),
            'annee' => $annee,
            'mois' => $mois,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'mode_paiement_id' => 'nullable|exists:modes_paiement,id',
            'statut_cotisation_id' => 'nullable|exists:statuts_cotisation,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['enregistre_par'] = auth()->id();

        Cotisation::create($validated);

        return redirect()->route('cotisations.index')
            ->with('success', 'Cotisation enregistrée avec succès.');
    }

    /**
     * Store multiple cotisations at once.
     */
    public function storeMultiple(Request $request)
    {
        $validated = $request->validate([
            'cotisations' => 'required|array|min:1',
            'cotisations.*.membre_id' => 'required|exists:membres,id',
            'cotisations.*.montant' => 'required|numeric|min:0',
            'cotisations.*.annee' => 'required|string',
            'cotisations.*.mois' => 'required|string',
            'cotisations.*.date_paiement' => 'nullable|date',
            'cotisations.*.mode_paiement_id' => 'nullable|exists:modes_paiement,id',
            'cotisations.*.statut_cotisation_id' => 'nullable|exists:statuts_cotisation,id',
            'cotisations.*.reference' => 'nullable|string|max:255',
            'cotisations.*.notes' => 'nullable|string',
        ]);

        $enregistrePar = auth()->id();
        $created = 0;
        $updated = 0;

        foreach ($validated['cotisations'] as $cotisationData) {
            // Vérifier si une cotisation existe déjà pour ce membre/année/mois
            $cotisationExistante = Cotisation::where('membre_id', $cotisationData['membre_id'])
                ->where('annee', $cotisationData['annee'])
                ->where('mois', $cotisationData['mois'])
                ->first();

            $cotisationData['enregistre_par'] = $enregistrePar;

            if ($cotisationExistante) {
                // Mettre à jour la cotisation existante
                $cotisationExistante->update($cotisationData);
                $updated++;
            } else {
                // Créer une nouvelle cotisation
                Cotisation::create($cotisationData);
                $created++;
            }
        }

        $message = [];
        if ($created > 0) {
            $message[] = "{$created} cotisation(s) créée(s)";
        }
        if ($updated > 0) {
            $message[] = "{$updated} cotisation(s) mise(s) à jour";
        }

        return redirect()->route('cotisations.index')
            ->with('success', implode(' et ', $message) . ' avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cotisation $cotisation): Response
    {
        return Inertia::render('Cotisations/Edit', [
            'cotisation' => $cotisation->load(['membre', 'modePaiement', 'statutCotisation']),
            'membres' => Membre::orderBy('prenom')->get(['id', 'prenom', 'nom']),
            'modesPaiement' => ModePaiement::where('actif', 1)->orderBy('libelle')->get(),
            'statutsCotisation' => StatutCotisation::orderBy('libelle')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cotisation $cotisation)
    {
        $validated = $request->validate([
            'membre_id' => 'required|exists:membres,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'mode_paiement_id' => 'nullable|exists:modes_paiement,id',
            'statut_cotisation_id' => 'nullable|exists:statuts_cotisation,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $cotisation->update($validated);

        return redirect()->route('cotisations.index')
            ->with('success', 'Cotisation mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cotisation $cotisation)
    {
        $cotisation->delete();

        return redirect()->route('cotisations.index')
            ->with('success', 'Cotisation supprimée avec succès.');
    }

    /**
     * Print report of cotisations based on filters.
     */
    public function print(Request $request)
    {
        $query = Cotisation::with(['membre.localite', 'modePaiement', 'statutCotisation']);

        $filters = [];
        
        if ($request->has('membre_id') && $request->membre_id) {
            $query->where('membre_id', $request->membre_id);
            $filters['membre'] = Membre::find($request->membre_id);
        }

        if ($request->has('localite_id') && $request->localite_id) {
            $query->whereHas('membre', function ($q) use ($request) {
                $q->where('localite_id', $request->localite_id);
            });
            $filters['localite'] = Localite::find($request->localite_id);
        }

        if ($request->has('annee') && $request->annee) {
            $query->where('annee', $request->annee);
            $filters['annee'] = $request->annee;
        }

        if ($request->has('mois') && $request->mois) {
            $query->where('mois', $request->mois);
            $moisNoms = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];
            $filters['mois'] = $moisNoms[$request->mois] ?? $request->mois;
        }

        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $titre = 'COTISATIONS ZIKOBOUÉ POUR LA VIE';
        $tableauxParMois = [];
        
        // Déterminer les mois à afficher
        if (isset($filters['annee']) && !isset($filters['mois'])) {
            // Seule l'année est sélectionnée : tous les mois de l'année
            $annee = $filters['annee'];
            $moisAAfficher = range(1, 12);
        } elseif (isset($filters['localite']) && !isset($filters['annee']) && !isset($filters['mois'])) {
            // Seule la localité est sélectionnée : mois passés et actuel de l'année en cours
            $annee = date('Y');
            $moisActuel = date('n');
            $moisAAfficher = range(1, $moisActuel);
        } elseif (!isset($filters['annee']) && !isset($filters['mois'])) {
            // Aucun filtre : mois passés et actuel de l'année en cours
            $annee = date('Y');
            $moisActuel = date('n');
            $moisAAfficher = range(1, $moisActuel);
        } else {
            // Mois spécifique sélectionné ou autres cas
            $annee = $filters['annee'] ?? date('Y');
            $moisAAfficher = isset($filters['mois']) ? [$request->mois] : [date('n')];
        }
        
        // Pour chaque mois, récupérer les cotisations et les grouper
        foreach ($moisAAfficher as $mois) {
            $queryMois = Cotisation::with(['membre.localite', 'modePaiement', 'statutCotisation']);
            
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
            $queryMois->where('annee', $annee)->where('mois', $mois);
            
            $cotisationsMois = $queryMois->latest('date_paiement')->get();
            
            if ($cotisationsMois->isEmpty()) {
                continue; // Passer au mois suivant si aucune cotisation
            }
            
            // Grouper les cotisations par localité
            $cotisationsParLocalite = $cotisationsMois->groupBy(function ($cotisation) {
                return $cotisation->membre->localite ? $cotisation->membre->localite->libelle : 'Sans localité';
            });
            
            $montantTotal = $cotisationsMois->sum('montant');
            
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
                'cotisationsParLocalite' => $cotisationsParLocalite,
                'montantTotal' => $montantTotal,
                'cotisations' => $cotisationsMois,
            ];
        }
        
        // Si aucun tableau n'a été généré (aucune cotisation), créer un tableau vide
        if (empty($tableauxParMois)) {
            $annee = $filters['annee'] ?? date('Y');
            $mois = isset($filters['mois']) ? $request->mois : date('n');
            $sousTitre = isset($filters['localite']) && !isset($filters['mois']) 
                ? $filters['localite']->libelle 
                : $moisNoms[$mois] . ' ' . $annee;
            
            $tableauxParMois[] = [
                'mois' => $mois,
                'moisNom' => $moisNoms[$mois],
                'annee' => $annee,
                'sousTitre' => $sousTitre,
                'cotisationsParLocalite' => collect(),
                'montantTotal' => 0,
                'cotisations' => collect(),
            ];
        }

        return view('cotisations.print', [
            'tableauxParMois' => $tableauxParMois,
            'filters' => $filters,
            'titre' => $titre,
            'localiteSelectionnee' => isset($filters['localite']),
        ]);
    }
}
