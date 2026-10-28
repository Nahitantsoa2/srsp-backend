<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueStatut extends Model
{
    protected $table = 'historique_statut';

    protected $primaryKey = 'id_historique';

    public $timestamps = false;

    protected $fillable = [
        'ancien_statut',
        'nouveau_statut',
        'id_demande',
    ];

    protected $casts = [
        'date_changement' => 'datetime',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class, 'id_demande');
    }
}
