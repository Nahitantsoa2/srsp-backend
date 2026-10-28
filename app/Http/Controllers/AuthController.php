<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Service;
use App\Models\Utilisateur;
use App\Services\RejetDossierNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/register — auto-inscription, réservée au rôle fonctionnaire
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        // Le formulaire ne demande plus le service : on assigne automatiquement
        // le premier service existant (ou on en crée un par défaut s'il n'y en a aucun).
        // L'administrateur pourra réaffecter l'utilisateur au bon service plus tard.
        $service = Service::first() ?? Service::create([
            'nom_service' => 'Non affecté',
            'description' => 'Service par défaut assigné à l\'inscription',
        ]);

        $utilisateur = Utilisateur::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            // L'IM (matricule) est facultatif : un nouvel agent peut ne pas encore
            // en disposer. Il sera renseigné plus tard et sert alors à relier le
            // compte aux dossiers de suivi et à vérifier l'identité à la connexion.
            'matricule' => trim((string) ($data['matricule'] ?? '')),
            'mot_de_passe' => Hash::make($data['password']),
            // Le rôle est toujours "fonctionnaire" ici — jamais transmis par le
            // formulaire, pour empêcher quiconque de s'auto-attribuer un rôle
            // chef_service ou administrateur via cette route publique.
            'role' => 'fonctionnaire',
            'id_service' => $service->id_service,
        ]);

        $request->session()->regenerate();
        Auth::guard('web')->login($utilisateur);

        $this->notifierRejets($utilisateur);

        return response()->json([
            'utilisateur' => $utilisateur->load('service'),
        ], 201);
    }

    // POST /api/login — authentification par session (cookie httpOnly)
    public function login(LoginRequest $request)
    {
        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $utilisateur = $request->user();

        // Pour un fonctionnaire : si un matricule (IM) est enregistré sur le
        // compte, l'IM saisi doit correspondre. Si le compte n'a pas encore de
        // matricule (ex. nouveau compte), la connexion reste possible sans IM.
        if ($utilisateur->role === 'fonctionnaire') {
            $enregistre = trim((string) $utilisateur->matricule);

            if ($enregistre !== '') {
                $saisi = trim((string) $request->input('matricule'));

                if ($saisi === '' || $saisi !== $enregistre) {
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    throw ValidationException::withMessages([
                        'matricule' => ['IM (matricule) incorrect ou non associé à ce compte.'],
                    ]);
                }
            }
        }

        $request->session()->regenerate();

        // Notifie automatiquement le fonctionnaire de ses éventuels dossiers
        // rejetés (in-app + email), même importés après la création du compte.
        $this->notifierRejets($utilisateur);

        return response()->json([
            'utilisateur' => $utilisateur->load('service'),
        ]);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    // GET /api/moi
    public function moi(Request $request)
    {
        return response()->json($request->user()->load('service'));
    }

    /**
     * Envoie au fonctionnaire les notifications de rejet non encore envoyées.
     * Un échec (ex. email) ne doit jamais empêcher l'inscription/connexion.
     */
    private function notifierRejets(Utilisateur $utilisateur): void
    {
        try {
            app(RejetDossierNotifier::class)->notifierPourFonctionnaire($utilisateur);
        } catch (\Throwable $e) {
            Log::warning(
                "Échec de la notification des rejets à la connexion pour {$utilisateur->email} : {$e->getMessage()}"
            );
        }
    }
}
