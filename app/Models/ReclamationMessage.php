<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclamationMessage extends Model
{
    protected $table = 'reclamation_message';

    protected $primaryKey = 'id_message';

    public $timestamps = false;

    protected $fillable = [
        'id_reclamation',
        'id_utilisateur',
        'contenu',
        'date_envoi',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function masques()
    {
        return $this->hasMany(ReclamationMessageMasque::class, 'id_message');
    }
}
