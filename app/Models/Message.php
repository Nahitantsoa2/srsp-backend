<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'message';

    protected $primaryKey = 'id_message';

    public $timestamps = false;

    protected $fillable = [
        'contenu',
        'expediteur',
        'id_conversation',
        'id_agent',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'id_conversation');
    }

    public function agent()
    {
        return $this->belongsTo(AgentIa::class, 'id_agent');
    }
}
