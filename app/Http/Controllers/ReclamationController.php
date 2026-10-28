<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjouterMessageReclamationRequest;
use App\Http\Requests\RepondreReclamationRequest;
use App\Http\Requests\StoreReclamationRequest;
use App\Http\Requests\SupprimerMessageReclamationRequest;
use App\Models\Reclamation;
use App\Models\ReclamationMessageMasque;
use App\Models\Utilisateur;
use Illuminate\Http\Request;

class ReclamationController extends Controller
{
    // POST /api/reclamations — le fonctionnaire dépose une réclamation
    public function store(StoreReclamationRequest $request)
    {
        $data = $request->validated();

        $reclamation = Reclamation::create([
            'objet' => $data['objet'],
            'description' => $data['description'],
            'id_demande' => $data['id_demande'] ?? null,
            'id_fonctionnaire' => $request->user()->id_utilisateur,
            'statut' => 'ouverte',
        ]);

        // Premier message de la discussion = la description initiale
        $reclamation->messages()->create([
            'id_utilisateur' => $request->user()->id_utilisateur,
            'contenu' => $data['description'],
        ]);

        return response()->json($this->formater($reclamation->load(['demande', 'messages.utilisateur', 'messages.masques']), $request->user()->id_utilisateur), 201);
    }

    // POST /api/reclamations/{id}/messages — le fonctionnaire relance la discussion
    public function ajouterMessage(AjouterMessageReclamationRequest $request, $id)
    {
        $data = $request->validated();

        $reclamation = Reclamation::findOrFail($id);

        if ($reclamation->id_fonctionnaire !== $request->user()->id_utilisateur) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($reclamation->statut === 'cloturee') {
            return response()->json(['message' => 'Cette réclamation est clôturée.'], 422);
        }

        $reclamation->messages()->create([
            'id_utilisateur' => $request->user()->id_utilisateur,
            'contenu' => $data['contenu'],
        ]);

        // La discussion repasse en attente de traitement côté administration
        $reclamation->update(['statut' => 'ouverte']);

        foreach (Utilisateur::where('role', 'administrateur')->pluck('id_utilisateur') as $adminId) {
            NotificationController::creerPour(
                $adminId,
                "Nouveau message du fonctionnaire sur la réclamation \"{$reclamation->objet}\"."
            );
        }

        return response()->json(
            $this->formater($reclamation->fresh(['demande', 'chefTraitant', 'messages.utilisateur', 'messages.masques']), $request->user()->id_utilisateur),
            201
        );
    }

    // DELETE /api/reclamations/{id}/messages/{messageId} — supprimer un message
    // mode=moi  : masqué uniquement pour le fonctionnaire connecté
    // mode=tous : supprimé pour tout le monde (uniquement ses propres messages)
    public function supprimerMessage(SupprimerMessageReclamationRequest $request, $id, $messageId)
    {
        $data = $request->validated();

        $reclamation = Reclamation::findOrFail($id);

        if ($reclamation->id_fonctionnaire !== $request->user()->id_utilisateur) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $message = $reclamation->messages()->whereKey($messageId)->firstOrFail();

        if ($data['mode'] === 'tous') {
            if ($message->id_utilisateur !== $request->user()->id_utilisateur) {
                return response()->json(['message' => 'Vous ne pouvez supprimer pour tout le monde que vos propres messages.'], 403);
            }
            $message->delete();
        } else {
            ReclamationMessageMasque::firstOrCreate([
                'id_message' => $message->id_message,
                'id_utilisateur' => $request->user()->id_utilisateur,
            ]);
        }

        return response()->json(['message' => 'Message supprimé.']);
    }

    // GET /api/reclamations/mes-reclamations — lecture seule pour le fonctionnaire
    public function mesReclamations(Request $request)
    {
        return Reclamation::where('id_fonctionnaire', $request->user()->id_utilisateur)
            ->with(['demande', 'chefTraitant', 'messages.utilisateur', 'messages.masques'])
            ->orderByDesc('date_creation')
            ->get()
            ->map(function ($r) use ($request) {
                return $this->formater($r, $request->user()->id_utilisateur);
            });
    }

    // GET /api/chef/reclamations — accès EXCLUSIF au chef de service de son service
    public function reclamationsRecues(Request $request)
    {
        return Reclamation::whereHas('fonctionnaire', function ($q) use ($request) {
            $q->where('id_service', $request->user()->id_service);
        })
            ->with(['fonctionnaire', 'demande', 'messages.utilisateur'])
            ->orderBy('date_creation')
            ->get();
    }

    // GET /api/admin/reclamations — toutes les réclamations (admin)
    public function adminReclamations(Request $request)
    {
        return Reclamation::with(['fonctionnaire.service', 'demande', 'chefTraitant', 'messages.utilisateur', 'messages.masques'])
            ->orderByDesc('date_creation')
            ->get()
            ->map(function ($r) use ($request) {
                return $this->formater($r, $request->user()->id_utilisateur);
            });
    }

    // PATCH /api/chef/reclamations/{id}/repondre (aussi /api/admin/reclamations/{id}/repondre)
    public function repondre(RepondreReclamationRequest $request, $id)
    {
        $data = $request->validated();

        $reclamation = Reclamation::with('fonctionnaire')->findOrFail($id);

        if ($request->user()->role !== 'administrateur'
            && $reclamation?->fonctionnaire?->id_service !== $request->user()->id_service) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $reclamation->update([
            'reponse' => $data['reponse'],
            'statut' => $data['statut'],
            'date_reponse' => now(),
            'id_chef_traitant' => $request->user()->id_utilisateur,
        ]);

        // Ajoute la réponse dans le fil de discussion
        $reclamation->messages()->create([
            'id_utilisateur' => $request->user()->id_utilisateur,
            'contenu' => $data['reponse'],
        ]);

        NotificationController::creerPour(
            $reclamation->id_fonctionnaire,
            "Votre réclamation \"{$reclamation->objet}\" a reçu une réponse."
        );

        return response()->json($this->formater($reclamation->fresh(['fonctionnaire', 'demande', 'chefTraitant', 'messages.utilisateur', 'messages.masques']), $request->user()->id_utilisateur));
    }

    // Met en forme une réclamation pour l'API (dated ISO + labels lisibles)
    private function formater(Reclamation $r, ?int $idUtilisateur = null): array
    {
        return [
            'id_reclamation' => $r->id_reclamation,
            'objet' => $r->objet,
            'description' => $r->description,
            'statut' => $r->statut,
            'reponse' => $r->reponse,
            'date_creation' => optional($r->date_creation)->format('d/m/Y H:i'),
            'date_reponse' => optional($r->date_reponse)->format('d/m/Y H:i'),
            'id_fonctionnaire' => $r->id_fonctionnaire,
            'id_demande' => $r->id_demande,
            'fonctionnaire' => $r->relationLoaded('fonctionnaire') ? [
                'id_utilisateur' => $r->fonctionnaire?->id_utilisateur,
                'nom' => $r->fonctionnaire?->nom,
                'prenom' => $r->fonctionnaire?->prenom,
                'email' => $r->fonctionnaire?->email,
                'service' => $r->fonctionnaire?->service?->nom_service ?? null,
            ] : null,
            'chef_traitant' => $r->relationLoaded('chefTraitant') && $r->chefTraitant ? [
                'id_utilisateur' => $r->chefTraitant->id_utilisateur,
                'nom' => $r->chefTraitant->nom,
                'prenom' => $r->chefTraitant->prenom,
                'role' => $r->chefTraitant->role,
            ] : null,
            'demande' => $r->relationLoaded('demande') ? [
                'id_demande' => $r->demande?->id_demande,
                'statut' => $r->demande?->statut,
                'type' => $r->demande?->typeDemarche?->libelle ?? null,
            ] : null,
            'messages' => $r->relationLoaded('messages') ? $r->messages
                ->filter(function ($m) use ($idUtilisateur) {
                    if ($idUtilisateur === null || ! $m->relationLoaded('masques')) {
                        return true;
                    }

                    return ! $m->masques->contains('id_utilisateur', $idUtilisateur);
                })
                ->map(function ($m) {
                    return [
                        'id_message' => $m->id_message,
                        'contenu' => $m->contenu,
                        'date_envoi' => optional($m->date_envoi)->format('d/m/Y H:i'),
                        'auteur' => $m->relationLoaded('utilisateur') && $m->utilisateur ? [
                            'id_utilisateur' => $m->utilisateur->id_utilisateur,
                            'nom' => $m->utilisateur->nom,
                            'prenom' => $m->utilisateur->prenom,
                            'role' => $m->utilisateur->role,
                        ] : null,
                    ];
                })->values() : [],
        ];
    }
}
