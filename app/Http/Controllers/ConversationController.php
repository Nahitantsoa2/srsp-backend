<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\AgentIa;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AssistantIaService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private AssistantIaService $assistant) {}

    // GET /api/conversations — liste des conversations de l'utilisateur
    public function index(Request $request)
    {
        $conversations = Conversation::where('id_utilisateur', $request->user()->id_utilisateur)
            ->orderBy('date_debut', 'desc')
            ->get();

        return response()->json($conversations);
    }

    // POST /api/conversations — démarre une nouvelle conversation
    public function store(Request $request)
    {
        $conversation = Conversation::create([
            'id_utilisateur' => $request->user()->id_utilisateur,
        ]);

        return response()->json($conversation, 201);
    }

    // GET /api/conversations/{id}/messages
    public function messages(Request $request, $id)
    {
        $conversation = $this->conversationDeLUtilisateur($request, $id);

        return $conversation->messages()->with('agent')->get();
    }

    // POST /api/conversations/{id}/messages — enregistre le message puis la réponse de l'agent
    public function envoyerMessage(StoreMessageRequest $request, $id)
    {
        $conversation = $this->conversationDeLUtilisateur($request, $id);

        // 1. Enregistrer le message de l'utilisateur
        Message::create([
            'contenu' => $request->validated('contenu'),
            'expediteur' => 'utilisateur',
            'id_conversation' => $conversation->id_conversation,
        ]);

        // 2. Appeler l'agent d'assistance (LLM)
        $reponseIa = $this->assistant->repondre($request->validated('contenu'));

        $agentAssistance = AgentIa::where('type_agent', 'assistance')->first();

        // 3. Enregistrer la réponse de l'agent
        $messageAgent = Message::create([
            'contenu' => $reponseIa,
            'expediteur' => 'agent_ia',
            'id_conversation' => $conversation->id_conversation,
            'id_agent' => $agentAssistance?->id_agent,
        ]);

        return response()->json($messageAgent->load('agent'));
    }

    // DELETE /api/conversations/{id} — supprime une conversation (et ses messages)
    public function destroy(Request $request, $id)
    {
        $conversation = $this->conversationDeLUtilisateur($request, $id);

        // Supprime d'abord les messages liés (évite les erreurs de contrainte de clé étrangère)
        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['message' => 'Conversation supprimée avec succès'], 200);
    }

    // DELETE /api/conversations — supprime TOUTES les conversations de l'utilisateur connecté
    public function destroyAll(Request $request)
    {
        $conversations = Conversation::where('id_utilisateur', $request->user()->id_utilisateur)->get();

        foreach ($conversations as $conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        }

        return response()->json(['message' => 'Toutes les conversations ont été supprimées'], 200);
    }

    /**
     * Récupère une conversation en la restreignant à l'utilisateur connecté.
     */
    private function conversationDeLUtilisateur(Request $request, $id): Conversation
    {
        return Conversation::where('id_utilisateur', $request->user()->id_utilisateur)
            ->findOrFail($id);
    }
}
