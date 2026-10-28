<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreerDemandeIaRequest;
use App\Http\Requests\StoreDemandeRequest;
use App\Http\Requests\TraiterDemandeRequest;
use App\Models\Demande;
use App\Models\HistoriqueStatut;
use App\Models\TypeDemarche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DemandeController extends Controller
{
    // POST /api/demandes/ia — création automatique à partir d'un texte libre
    // Ex: "Je veux une demande de pension, nom Rakoto Jean, matricule 4521"
    public function creerAvecIA(CreerDemandeIaRequest $request)
    {
        $data = $request->validated();

        $typesDisponibles = TypeDemarche::pluck('libelle', 'id_type_demarche');

        $extraction = $this->extraireInformations($data['message'], $typesDisponibles);

        if (! $extraction || empty($extraction['id_type_demarche'])) {
            return response()->json([
                'message' => "Je n'ai pas pu identifier le type de démarche. Précisez par exemple : \"demande de pension\" ou \"demande de congé\".",
            ], 422);
        }

        $demande = Demande::create([
            'id_fonctionnaire' => $request->user()->id_utilisateur,
            'id_type_demarche' => $extraction['id_type_demarche'],
            'statut' => 'soumise',
            'details' => $extraction['details'] ?? [],
        ]);

        HistoriqueStatut::create([
            'id_demande' => $demande->id_demande,
            'ancien_statut' => null,
            'nouveau_statut' => 'soumise',
        ]);

        return response()->json([
            'demande' => $demande->load('typeDemarche'),
            'resume' => $extraction['resume'] ?? 'Demande créée avec succès.',
        ], 201);
    }

    // Appelle le LLM pour transformer le texte libre en données structurées
    private function extraireInformations(string $message, $typesDisponibles): ?array
    {
        $listeTypes = $typesDisponibles
            ->map(fn ($libelle, $id) => "$id: $libelle")
            ->implode("\n");

        $systemPrompt = <<<PROMPT
Tu es l'Agent d'Orientation d'un système administratif. À partir du message d'un fonctionnaire,
identifie le type de démarche parmi cette liste (id: libellé) :
{$listeTypes}

Extrait aussi les informations utiles mentionnées (nom, matricule, dates, motif...).

Réponds UNIQUEMENT en JSON valide, sans texte autour, au format exact :
{"id_type_demarche": <id ou null>, "details": {"cle": "valeur", ...}, "resume": "phrase courte confirmant ce qui a été compris"}
PROMPT;

        try {
            $apiKey = config('services.anthropic.key');
            if (empty($apiKey) || $apiKey === 'votre_cle_api_ici') {
                // Simulation of API response if no valid key is provided
                return [
                    'id_type_demarche' => $typesDisponibles->keys()->first(),
                    'details' => ['simulé' => 'vrai', 'message' => $message],
                    'resume' => 'Demande simulée à partir de : '.$message,
                ];
            }

            $response = Http::withoutVerifying()->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model') ?? 'claude-3-5-sonnet-20240620',
                'max_tokens' => 400,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $message],
                ],
            ]);

            if ($response->failed()) {
                return null;
            }

            $texte = $response->json('content.0.text');

            // Sécurité : extrait le premier bloc JSON valide de la réponse
            preg_match('/\{.*\}/s', $texte, $matches);

            return $matches ? json_decode($matches[0], true) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // POST /api/demandes — le fonctionnaire soumet une nouvelle démarche (formulaire manuel)
    public function store(StoreDemandeRequest $request)
    {
        $data = $request->validated();

        $demande = Demande::create([
            'id_fonctionnaire' => $request->user()->id_utilisateur,
            'id_type_demarche' => $data['id_type_demarche'],
            'statut' => 'soumise',
            'details' => $data['details'] ?? [],
        ]);

        HistoriqueStatut::create([
            'id_demande' => $demande->id_demande,
            'ancien_statut' => null,
            'nouveau_statut' => 'soumise',
        ]);

        return response()->json($demande->load('typeDemarche'), 201);
    }

    // GET /api/demandes/mes-demandes — suivi en lecture seule pour le fonctionnaire
    public function mesDemandes(Request $request)
    {
        return Demande::where('id_fonctionnaire', $request->user()->id_utilisateur)
            ->with(['typeDemarche'])
            ->orderByDesc('date_soumission')
            ->get();
    }

    // GET /api/demandes/{id} — détail d'une démarche
    public function show(Request $request, $id)
    {
        $demande = Demande::with(['typeDemarche', 'historique', 'documents', 'fonctionnaire'])
            ->findOrFail($id);

        // Un fonctionnaire ne peut voir que ses propres démarches
        if ($request->user()->role === 'fonctionnaire'
            && $demande->id_fonctionnaire !== $request->user()->id_utilisateur) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return response()->json($demande);
    }

    // GET /api/chef/demandes — file d'attente du chef de service
    public function demandesAtraiter(Request $request)
    {
        return Demande::whereHas('fonctionnaire', function ($q) use ($request) {
            $q->where('id_service', $request->user()->id_service);
        })
            ->where('statut', 'soumise')
            ->with(['fonctionnaire', 'typeDemarche'])
            ->orderBy('date_soumission')
            ->get();
    }

    // PATCH /api/chef/demandes/{id}/traiter — validation / rejet
    public function traiter(TraiterDemandeRequest $request, $id)
    {
        $data = $request->validated();

        $demande = Demande::with('fonctionnaire')->findOrFail($id);

        if ($demande?->fonctionnaire?->id_service !== $request->user()->id_service) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $ancienStatut = $demande->statut;

        $demande->update([
            'statut' => $data['statut'],
            'id_chef_traitant' => $request->user()->id_utilisateur,
            'date_traitement' => now(),
        ]);

        HistoriqueStatut::create([
            'id_demande' => $demande->id_demande,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $data['statut'],
        ]);

        // Notifier le fonctionnaire (voir NotificationController::creerPour)
        NotificationController::creerPour(
            $demande->id_fonctionnaire,
            "Votre démarche #{$demande->id_demande} est maintenant : {$data['statut']}"
        );

        return response()->json($demande->fresh(['typeDemarche', 'historique']));
    }

    // GET /api/chef/tableau-de-bord — statistiques simples
    public function statistiques(Request $request)
    {
        $idService = $request->user()->id_service;

        $base = Demande::whereHas('fonctionnaire', fn ($q) => $q->where('id_service', $idService));

        return response()->json([
            'total' => (clone $base)->count(),
            'en_attente' => (clone $base)->where('statut', 'soumise')->count(),
            'validees' => (clone $base)->where('statut', 'validee')->count(),
            'rejetees' => (clone $base)->where('statut', 'rejetee')->count(),
        ]);
    }
}
