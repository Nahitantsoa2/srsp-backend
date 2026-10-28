<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInformationRequest;
use App\Http\Requests\UpdateInformationRequest;
use App\Models\Information;
use Illuminate\Http\JsonResponse;

class InformationController extends Controller
{
    /**
     * Liste des agents, triée par localité puis par nom et prénom.
     * Utilisée par l'espace fonctionnaire (lecture seule).
     */
    public function index(): JsonResponse
    {
        $agents = Information::orderBy('localite')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        return response()->json($agents);
    }

    /**
     * Ajout d'un agent (réservé à l'administrateur).
     */
    public function store(StoreInformationRequest $request): JsonResponse
    {
        $information = Information::create($request->validated());

        return response()->json($information, 201);
    }

    /**
     * Modification d'un agent existant (réservé à l'administrateur).
     */
    public function update(UpdateInformationRequest $request, $id): JsonResponse
    {
        $information = Information::findOrFail($id);

        $information->update($request->validated());

        return response()->json($information);
    }

    /**
     * Suppression d'un agent (réservé à l'administrateur).
     */
    public function destroy($id): JsonResponse
    {
        Information::findOrFail($id)->delete();

        return response()->json(['message' => 'Information supprimée avec succès']);
    }
}
