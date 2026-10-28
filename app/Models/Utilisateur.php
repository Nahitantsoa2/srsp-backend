<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'utilisateur';

    protected $primaryKey = 'id_utilisateur';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'role',
        'id_service',
        'matricule',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    protected $casts = [
        'date_creation' => 'datetime',
    ];

    // Laravel utilise "password" par défaut pour l'authentification :
    // on redirige vers la colonne réelle "mot_de_passe"
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    // --- Relations ---

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_service');
    }

    // Demandes soumises par ce fonctionnaire
    public function demandesSoumises()
    {
        return $this->hasMany(Demande::class, 'id_fonctionnaire');
    }

    // Demandes traitées par ce chef de service
    public function demandesTraitees()
    {
        return $this->hasMany(Demande::class, 'id_chef_traitant');
    }

    // Réclamations déposées par ce fonctionnaire
    public function reclamationsDeposees()
    {
        return $this->hasMany(Reclamation::class, 'id_fonctionnaire');
    }

    // Réclamations traitées par ce chef de service
    public function reclamationsTraitees()
    {
        return $this->hasMany(Reclamation::class, 'id_chef_traitant');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'id_utilisateur');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'id_utilisateur');
    }
}
