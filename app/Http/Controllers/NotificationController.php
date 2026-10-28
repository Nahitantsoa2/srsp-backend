<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        return Notification::where('id_utilisateur', $request->user()->id_utilisateur)
            ->orderByDesc('date_envoi')
            ->get();
    }

    // PATCH /api/notifications/{id}/lu
    public function marquerLu(Request $request, $id)
    {
        $notification = Notification::where('id_utilisateur', $request->user()->id_utilisateur)
            ->findOrFail($id);

        $notification->update(['lu' => true]);

        return response()->json($notification);
    }

    // DELETE /api/notifications/{id}
    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('id_utilisateur', $request->user()->id_utilisateur)
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['message' => 'Notification supprimée']);
    }

    // Méthode utilitaire interne, appelée par les autres contrôleurs
    // (ex: DemandeController::traiter, ReclamationController::repondre)
    public static function creerPour(int $idUtilisateur, string $message): Notification
    {
        return Notification::create([
            'id_utilisateur' => $idUtilisateur,
            'message' => $message,
            'lu' => false,
        ]);
    }
}
