<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Reclamation;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistiquesController extends Controller
{
    /**
     * GET /api/admin/statistiques
     * Retourne toutes les données nécessaires pour le tableau de bord graphique.
     */
    public function index(Request $request)
    {
        return response()->json([
            'resume' => $this->resumeGeneral(),
            'demandes_mensuelles' => $this->demandesParMois(),
            'par_statut' => $this->demandesParStatut(),
            'par_type' => $this->demandesParType(),
            'par_service' => $this->demandesParService(),
            'evolution_hebdo' => $this->evolutionHebdomadaire(),
            'activite_recente' => $this->activiteRecente(),
        ]);
    }

    /**
     * KPIs globaux
     */
    private function resumeGeneral(): array
    {
        $totalDemandes = Demande::count();
        $moisCourant = now()->startOfMonth();
        $moisPrecedent = now()->subMonth()->startOfMonth();

        $demandesCeMois = Demande::where('date_soumission', '>=', $moisCourant)->count();
        $demandesMoisPrec = Demande::where('date_soumission', '>=', $moisPrecedent)
            ->where('date_soumission', '<', $moisCourant)->count();

        $tauxValidation = $totalDemandes > 0
            ? round(Demande::where('statut', 'validee')->count() / $totalDemandes * 100, 1)
            : 0;

        $tempsMoyenTraitement = Demande::whereNotNull('date_traitement')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (date_traitement - date_soumission)) / 86400) as jours')
            ->value('jours');

        return [
            'total_demandes' => $totalDemandes,
            'demandes_ce_mois' => $demandesCeMois,
            'demandes_mois_precedent' => $demandesMoisPrec,
            'evolution_mois' => $demandesMoisPrec > 0
                ? round(($demandesCeMois - $demandesMoisPrec) / $demandesMoisPrec * 100, 1)
                : ($demandesCeMois > 0 ? 100 : 0),
            'taux_validation' => $tauxValidation,
            'temps_moyen_jours' => $tempsMoyenTraitement ? round($tempsMoyenTraitement, 1) : null,
            'total_utilisateurs' => Utilisateur::count(),
            'total_reclamations' => Reclamation::count(),
        ];
    }

    /**
     * Nombre de demandes pour chaque mois des 12 derniers mois
     */
    private function demandesParMois(): array
    {
        $mois = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mois->push([
                'mois' => $date->format('M Y'),
                'cle' => $date->format('Y-m'),
                'total' => Demande::whereYear('date_soumission', $date->year)
                    ->whereMonth('date_soumission', $date->month)
                    ->count(),
                'validee' => Demande::whereYear('date_soumission', $date->year)
                    ->whereMonth('date_soumission', $date->month)
                    ->where('statut', 'validee')
                    ->count(),
                'rejetee' => Demande::whereYear('date_soumission', $date->year)
                    ->whereMonth('date_soumission', $date->month)
                    ->where('statut', 'rejetee')
                    ->count(),
                'soumise' => Demande::whereYear('date_soumission', $date->year)
                    ->whereMonth('date_soumission', $date->month)
                    ->where('statut', 'soumise')
                    ->count(),
            ]);
        }

        return $mois->toArray();
    }

    /**
     * Répartition des statuts
     */
    private function demandesParStatut(): array
    {
        $couleurs = [
            'soumise' => '#3b82f6',
            'validee' => '#10b981',
            'rejetee' => '#ef4444',
            'complement_demande' => '#f59e0b',
            'en_cours' => '#8b5cf6',
        ];

        return Demande::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->map(fn ($row) => [
                'name' => ucfirst(str_replace('_', ' ', $row->statut)),
                'value' => $row->total,
                'statut' => $row->statut,
                'color' => $couleurs[$row->statut] ?? '#6b7280',
            ])
            ->toArray();
    }

    /**
     * Nombre de demandes par type de démarche
     */
    private function demandesParType(): array
    {
        return Demande::join('type_demarche', 'demande.id_type_demarche', '=', 'type_demarche.id_type_demarche')
            ->select('type_demarche.libelle as type', DB::raw('count(*) as total'))
            ->groupBy('type_demarche.libelle')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Nombre de demandes par service
     */
    private function demandesParService(): array
    {
        return Demande::join('utilisateur', 'demande.id_fonctionnaire', '=', 'utilisateur.id_utilisateur')
            ->join('service', 'utilisateur.id_service', '=', 'service.id_service')
            ->select('service.nom_service as service', DB::raw('count(*) as total'))
            ->groupBy('service.nom_service')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Évolution hebdomadaire des 8 dernières semaines
     */
    private function evolutionHebdomadaire(): array
    {
        $semaines = collect();
        for ($i = 7; $i >= 0; $i--) {
            $debut = now()->subWeeks($i)->startOfWeek();
            $fin = $debut->copy()->endOfWeek();
            $semaines->push([
                'semaine' => 'S'.$debut->format('W'),
                'debut' => $debut->format('d/m'),
                'fin' => $fin->format('d/m'),
                'total' => Demande::whereBetween('date_soumission', [$debut, $fin])->count(),
            ]);
        }

        return $semaines->toArray();
    }

    /**
     * Les 10 dernières demandes soumises
     */
    private function activiteRecente(): array
    {
        return Demande::with(['fonctionnaire', 'typeDemarche'])
            ->orderByDesc('date_soumission')
            ->limit(10)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id_demande,
                'type' => $d->typeDemarche->libelle ?? 'Inconnu',
                'fonctionnaire' => trim(($d->fonctionnaire->prenom ?? '').' '.($d->fonctionnaire->nom ?? '')),
                'statut' => $d->statut,
                'date' => $d->date_soumission->format('d/m/Y H:i'),
            ])
            ->toArray();
    }
}
