<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReclamationMessageMasque extends Model
{
    protected $table = 'reclamation_message_masque';

    protected $primaryKey = 'id_masque';

    public $timestamps = false;

    protected $fillable = [
        'id_message',
        'id_utilisateur',
    ];
}
