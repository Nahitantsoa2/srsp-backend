<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportSuiviRequest;
use App\Models\SuiviDossier;
use App\Services\RejetDossierNotifier;
use App\Services\SuiviImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuiviDossierController extends Controller
{
    // GET /api/suivi — liste des dossiers (optionnellement filtrés par IM)
    public function index(Request $request)
    {
        $query = SuiviDossier::query();

        if ($request->has('im') && $request->input('im') !== '') {
            $query->where('im', $request->input('im'));
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    // POST /api/admin/suivi — ajout manuel d'un dossier (champs: localite, chap, im, cmv)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'im' => 'required|string|max:50',
            'localite' => 'nullable|string|max:100',
            'chap' => 'nullable|string|max:50',
            'cmv' => 'nullable|string|max:50',
        ]);

        $empreinte = SuiviDossier::empreinte([
            $validated['localite'] ?? '',
            $validated['chap'] ?? '',
            $validated['im'] ?? '',
            $validated['cmv'] ?? '',
        ]);

        if (SuiviDossier::where('empreinte', $empreinte)->exists()) {
            return response()->json([
                'message' => 'Ce dossier existe déjà dans la base.',
            ], 422);
        }

        $validated['empreinte'] = $empreinte;
        $dossier = SuiviDossier::create($validated);

        return response()->json($dossier, 201);
    }

    // DELETE /api/admin/suivi/{id} — suppression d'un dossier
    public function destroy($id)
    {
        $dossier = SuiviDossier::findOrFail($id);
        $dossier->delete();

        return response()->json(['message' => 'Dossier supprimé avec succès']);
    }

    // DELETE /api/admin/suivi — vider tous les dossiers
    public function destroyAll()
    {
        SuiviDossier::truncate();

        return response()->json(['message' => 'Tous les dossiers de suivi ont été supprimés']);
    }

    // GET /api/admin/suivi/modele — télécharge un fichier modèle CSV avec Localiter, chap, im, cmv
    public function telechargerModele(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele_suivi_dossier.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // En-têtes demandés : Localiter, chap, im, cmv
            fputcsv($handle, ['Localiter', 'chap', 'im', 'cmv'], ';');
            // Exemples avec les matricules demandés
            fputcsv($handle, ['304', '01', '222222', '13'], ';');
            fputcsv($handle, ['306', '02', '437640', '02'], ';');
            fputcsv($handle, ['Antananarivo', '12', '494089', '11'], ';');
            fclose($handle);
        }, 200, $headers);
    }

    // POST /api/admin/suivi/import — import d'un fichier Excel/CSV
    public function import(
        ImportSuiviRequest $request,
        SuiviImportService $service,
        RejetDossierNotifier $notifier,
    ) {
        try {
            $resultat = $service->importer($request->file('fichier')->path());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Fichier illisible : '.$e->getMessage(),
            ], 422);
        }

        $a = $resultat['ajoutes'];
        $i = $resultat['ignores'];
        $v = $resultat['invalides'];

        // Prévenir les fonctionnaires concernés par un dossier rejeté si applicable.
        $notifies = $notifier->notifier($resultat['nouveaux']);

        return response()->json([
            'message' => "Import terminé : {$a} dossier(s) ajouté(s), {$i} déjà présent(s)".
                ($v > 0 ? ", {$v} ligne(s) sans IM ignorée(s)" : '').'.'.
                ($notifies > 0 ? " {$notifies} dossier(s) rejeté(s) : notification in-app et email." : ''),
            'ajoutes' => $a,
            'ignores' => $i,
            'invalides' => $v,
            'total' => $resultat['total'],
            'notifies' => $notifies,
        ]);
    }
}
