<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reclamation extends Model
{
    protected $table = 'reclamation';

    protected $primaryKey = 'id_reclamation';

    public $timestamps = false;

    protected $fillable = [
        'objet',
        'description',
        'statut',
        'date_reponse',
        'reponse',
        'id_fonctionnaire',
        'id_demande',
        'id_chef_traitant',
    ];

    protected $casts = [
        'date_creation' => 'datetime',
        'date_reponse' => 'datetime',
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

    public function demande()
    {
        return $this->belongsTo(Demande::class, 'id_demande');
    }

    public function messages()
    {
        return $this->hasMany(ReclamationMessage::class, 'id_reclamation')
            ->orderBy('date_envoi')
            ->orderBy('id_message');
    }
}
