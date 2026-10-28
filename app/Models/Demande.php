<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    protected $table = 'demande';

    protected $primaryKey = 'id_demande';

    public $timestamps = false;

    protected $fillable = [
        'statut',
        'date_traitement',
        'id_fonctionnaire',
        'id_type_demarche',
        'id_chef_traitant',
    ];

    protected $casts = [
        'date_soumission' => 'datetime',
        'date_traitement' => 'datetime',
    ];

    // --- Relations ---

    public function fonctionnaire()
    {
        return $this->belongsTo(Utilisateur::class, 'id_fonctionnaire');
    }

    public function chefTraitant()
    {
        return $this->belongsTo(Utilisateur::class, 'id_chef_traitant');
    }

    public function typeDemarche()
    {
        return $this->belongsTo(TypeDemarche::class, 'id_type_demarche');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'id_demande');
    }

    public function historique()
    {
        return $this->hasMany(HistoriqueStatut::class, 'id_demande')->orderBy('date_changement');
    }

    public function reclamations()
    {
        return $this->hasMany(Reclamation::class, 'id_demande');
    }
}
