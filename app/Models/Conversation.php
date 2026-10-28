<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table = 'conversation';

    protected $primaryKey = 'id_conversation';

    public $timestamps = false;

    protected $fillable = [
        'id_utilisateur',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'id_conversation')->orderBy('date_envoi');
    }
}
