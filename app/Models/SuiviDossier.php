<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuiviDossier extends Model
{
    protected $table = 'suivi_dossier';

    protected $fillable = [
        'date_reception',
        'ref_be',
        'ref_dossier',
        'im',
        'nom_prenom',
        'ministere',
        'motif_instance',
        'detail_motif_instance',
        'date_leve_motif',
        'info_leve_motif',
        'sort_traitement',
        'motif_rejet',
        'detail_motif_rejet',
        'ref_operation',
        'traitement_augure',
        'date_traitement',
        'type_dossier',
        'nature_dossier',
        'detail_nature_dossier',
        'info_levee_motif',
        'date_leve_motif_2',
        'etat_dossier',
        'motif_rejet_2',
        'user',
        'empreinte',
    ];

    // Empreinte unique : évite les doublons lors d'un ré-import.
    public static function empreinte(array $ligne): string
    {
        $cle = implode('|', array_map(fn ($v) => mb_strtolower(trim((string) ($v ?? ''))), $ligne));

        return md5($cle);
    }

    // Un dossier est considéré rejeté quand le "sort traitement" mentionne
    // un rejet, mais pas "sans rejet" (qui est au contraire un traitement OK).
    public function estRejete(): bool
    {
        $sort = mb_strtolower(trim((string) $this->sort_traitement));

        return str_contains($sort, 'rejet') && ! str_contains($sort, 'sans rejet');
    }
}
