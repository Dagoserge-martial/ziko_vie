<?php

namespace App\Http\Controllers;

use App\Models\Cotisation;
use App\Models\DepenseMedicale;
use App\Models\Membre;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Récupérer le filtre année depuis la requête
        $anneeFiltre = null;
        if ($request->has('annee') && $request->annee !== null && $request->annee !== '') {
            $anneeFiltre = (int) $request->annee;
        }

        $totalMembres = Membre::count();
        $membresActifs = Membre::where('statut', 0)->count(); // 0 = actif, 1 = inactif
        
        // Filtrer les cotisations et dépenses selon l'année si sélectionnée
        $totalCotisationsQuery = Cotisation::query();
        $totalDepensesQuery = DepenseMedicale::query();
        
        if ($anneeFiltre) {
            // Les cotisations utilisent le champ 'annee' directement (casté en string dans le modèle)
            $totalCotisationsQuery->where('annee', (string) $anneeFiltre);
            // Les dépenses utilisent date_depense avec whereYear
            $totalDepensesQuery->whereYear('date_depense', $anneeFiltre);
        }
        
        $totalCotisations = $totalCotisationsQuery->sum('montant');
        // Utiliser montant_total pour les dépenses, ou montant si montant_total est null
        $totalDepenses = $totalDepensesQuery->get()->sum(function ($depense) {
            return (float) ($depense->montant_total ?? $depense->montant ?? 0);
        });
        $solde = $totalCotisations - $totalDepenses;

        // Cotisations et dépenses du mois en cours (ou de l'année sélectionnée)
        $anneePourMois = $anneeFiltre ?? now()->year;
        $moisActuel = now()->month;
        $cotisationsMois = Cotisation::where('annee', (string) $anneePourMois)
            ->where('mois', (string) $moisActuel)
            ->sum('montant');
        
        $depensesMoisQuery = DepenseMedicale::whereMonth('date_depense', $moisActuel)
            ->whereYear('date_depense', $anneePourMois);
        $depensesMois = $depensesMoisQuery->get()->sum(function ($depense) {
            return (float) ($depense->montant_total ?? $depense->montant ?? 0);
        });

        // Dernières cotisations (filtrées par année si sélectionnée)
        $dernieresCotisationsQuery = Cotisation::with(['membre', 'modePaiement', 'statutCotisation', 'enregistrePar'])
            ->orderBy('date_paiement', 'desc')
            ->orderBy('created_at', 'desc');
        
        if ($anneeFiltre) {
            // Utiliser le champ 'annee' directement pour les cotisations (casté en string dans le modèle)
            $dernieresCotisationsQuery->where('annee', (string) $anneeFiltre);
        }
        
        $dernieresCotisations = $dernieresCotisationsQuery->take(4)->get();

        // Dernières dépenses (filtrées par année si sélectionnée)
        $dernieresDepensesQuery = DepenseMedicale::with(['membre', 'categorieDepense', 'enregistrePar'])
            ->latest('date_depense');
        
        if ($anneeFiltre) {
            $dernieresDepensesQuery->whereYear('date_depense', $anneeFiltre);
        }
        
        $dernieresDepenses = $dernieresDepensesQuery->take(4)->get();

        // Membres à jour vs en retard
        $membresAJour = Membre::all()->filter(fn($m) => $m->isUpToDate())->count();
        $membresEnRetard = $totalMembres - $membresAJour;

        // Évolution des 6 derniers mois
        $statsMensuelles = [];
        if ($anneeFiltre) {
            // Si une année est sélectionnée, afficher les 6 derniers mois de cette année
            $moisMax = $anneeFiltre == now()->year ? now()->month : 12;
            $debut = max(1, $moisMax - 5); // Commencer 5 mois avant le mois maximum ou au mois 1
            
            for ($mois = $debut; $mois <= $moisMax; $mois++) {
                $date = \Carbon\Carbon::create($anneeFiltre, $mois, 1);
                $moisNoms = [
                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                ];
                $cotisationsMoisStat = Cotisation::where('annee', (string) $anneeFiltre)
                    ->where('mois', (string) $mois)
                    ->sum('montant');
                $depensesMoisQuery = DepenseMedicale::whereMonth('date_depense', $mois)
                    ->whereYear('date_depense', $anneeFiltre);
                $depensesMoisStat = $depensesMoisQuery->get()->sum(function ($depense) {
                    return (float) ($depense->montant_total ?? $depense->montant ?? 0);
                });
                $statsMensuelles[] = [
                    'mois' => $moisNoms[$mois] . ' ' . $anneeFiltre,
                    'cotisations' => $cotisationsMoisStat,
                    'depenses' => $depensesMoisStat,
                ];
            }
        } else {
            // Sinon, afficher les 6 derniers mois à partir de maintenant
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $moisNoms = [
                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                ];
                $cotisationsMoisStat = Cotisation::where('annee', (string) $date->year)
                    ->where('mois', (string) $date->month)
                    ->sum('montant');
                $depensesMoisQuery = DepenseMedicale::whereMonth('date_depense', $date->month)
                    ->whereYear('date_depense', $date->year);
                $depensesMoisStat = $depensesMoisQuery->get()->sum(function ($depense) {
                    return (float) ($depense->montant_total ?? $depense->montant ?? 0);
                });
                $statsMensuelles[] = [
                    'mois' => $moisNoms[$date->month] . ' ' . $date->year,
                    'cotisations' => $cotisationsMoisStat,
                    'depenses' => $depensesMoisStat,
                ];
            }
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalMembres' => $totalMembres,
                'membresActifs' => $membresActifs,
                'totalCotisations' => $totalCotisations,
                'totalDepenses' => $totalDepenses,
                'solde' => $solde,
                'cotisationsMois' => $cotisationsMois,
                'depensesMois' => $depensesMois,
                'membresAJour' => $membresAJour,
                'membresEnRetard' => $membresEnRetard,
            ],
            'dernieresCotisations' => $dernieresCotisations,
            'dernieresDepenses' => $dernieresDepenses,
            'statsMensuelles' => $statsMensuelles,
            'filters' => [
                'annee' => $anneeFiltre,
            ],
        ]);
    }
}
