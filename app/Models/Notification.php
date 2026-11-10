<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    protected $primaryKey = 'id_notification';

    public $timestamps = false;

    protected $fillable = [
        'message',
        'lu',
        'email_envoye',
        'id_utilisateur',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
        'lu' => 'boolean',
        'email_envoye' => 'boolean',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }
}
