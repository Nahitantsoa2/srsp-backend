<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUtilisateurRequest;
use App\Http\Requests\UpdateUtilisateurRequest;
use App\Models\Demande;
use App\Models\Reclamation;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UtilisateurController extends Controller
{
    // GET /api/admin/utilisateurs
    public function index()
    {
        return Utilisateur::with('service')->orderBy('nom')->get();
    }

    // POST /api/admin/utilisateurs
    public function store(StoreUtilisateurRequest $request)
    {
        $data = $request->validated();

        $utilisateur = Utilisateur::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'mot_de_passe' => Hash::make($data['password']),
            'role' => $data['role'],
            'id_service' => $data['id_service'],
        ]);

        return response()->json($utilisateur, 201);
    }

    // GET /api/admin/utilisateurs/{id}
    public function show($id)
    {
        return Utilisateur::with('service')->findOrFail($id);
    }

    // PUT/PATCH /api/admin/utilisateurs/{id}
    public function update(UpdateUtilisateurRequest $request, $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        $data = $request->validated();

        $utilisateur->update($data);

        return response()->json($utilisateur);
    }

    // DELETE /api/admin/utilisateurs/{id}
    public function destroy(Request $request, $id)
    {
        // On empêche un administrateur de supprimer son propre compte.
        if ((int) $id === (int) $request->user()->id_utilisateur) {
            return response()->json(
                ['message' => 'Vous ne pouvez pas supprimer votre propre compte.'],
                403
            );
        }

        $utilisateur = Utilisateur::findOrFail($id);
        $idUtilisateur = $utilisateur->id_utilisateur;

        DB::transaction(function () use ($utilisateur, $idUtilisateur) {
            // Ces deux tables référencent l'utilisateur avec une contrainte
            // RESTRICT : il faut les vider d'abord sinon Postgres refuse la
            // suppression. Le reste (documents, historiques, messages,
            // notifications, conversations) est supprimé en cascade par la base.
            Demande::where('id_fonctionnaire', $idUtilisateur)->delete();
            Reclamation::where('id_fonctionnaire', $idUtilisateur)->delete();

            $utilisateur->delete();
        });

        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}
