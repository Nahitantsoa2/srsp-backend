<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'document';

    protected $primaryKey = 'id_document';

    public $timestamps = false;

    protected $fillable = [
        'nom_fichier',
        'chemin_fichier',
        'id_demande',
    ];

    protected $casts = [
        'date_upload' => 'datetime',
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class, 'id_demande');
    }
}
