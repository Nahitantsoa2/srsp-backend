<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;

class ServiceController extends Controller
{
    // GET /api/services
    // Accessible à tous les utilisateurs connectés (ex: liste déroulante lors d'une inscription,
    // ou pour choisir le service concerné par une démarche)
    public function index()
    {
        $services = Service::withCount(['utilisateurs', 'typesDemarche'])
            ->orderBy('nom_service')
            ->get();

        return response()->json($services);
    }

    // GET /api/services/{id}
    public function show($id)
    {
        $service = Service::withCount(['utilisateurs', 'typesDemarche'])
            ->with('typesDemarche')
            ->findOrFail($id);

        return response()->json($service);
    }

    // POST /api/admin/services — réservé à l'administrateur
    public function store(StoreServiceRequest $request)
    {
        $data = $request->validated();

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    // PUT/PATCH /api/admin/services/{id} — réservé à l'administrateur
    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::findOrFail($id);

        $data = $request->validated();

        $service->update($data);

        return response()->json($service);
    }

    // DELETE /api/admin/services/{id} — réservé à l'administrateur
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        // Empêche la suppression si des utilisateurs ou démarches en dépendent encore
        if ($service->utilisateurs()->exists() || $service->typesDemarche()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce service : il est encore utilisé par des utilisateurs ou des types de démarche.',
            ], 409);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé avec succès']);
    }
}
