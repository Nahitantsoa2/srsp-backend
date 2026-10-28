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
        'id_utilisateur',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
        'lu' => 'boolean',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }
}
