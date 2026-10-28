<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportSuiviRequest;
use App\Models\SuiviDossier;
use App\Services\RejetDossierNotifier;
use App\Services\SuiviImportService;
use Illuminate\Http\Request;

class SuiviDossierController extends Controller
{
    // GET /api/suivi — liste des dossiers (optionnellement filtrés par IM)
    public function index(Request $request)
    {
        $query = SuiviDossier::query();

        if ($request->has('im') && $request->input('im') !== '') {
            $query->where('im', $request->input('im'));
        }

        return response()->json($query->orderBy('id')->get());
    }

    // POST /api/admin/suivi/import — import d'un fichier Excel/CSV (une ligne par dossier)
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

        // Prévenir les fonctionnaires concernés par un dossier rejeté.
        $notifies = $notifier->notifier($resultat['nouveaux']);

        return response()->json([
            'message' => "Import terminé : {$a} dossier(s) ajouté(s), {$i} déjà présent(s)".
                ($v > 0 ? ", {$v} ligne(s) sans IM ignorée(s)" : '').'.'.
                ($notifies > 0 ? " {$notifies} fonctionnaire(s) notifié(s) par email pour rejet." : ''),
            'ajoutes' => $a,
            'ignores' => $i,
            'invalides' => $v,
            'total' => $resultat['total'],
            'notifies' => $notifies,
        ]);
    }
}
