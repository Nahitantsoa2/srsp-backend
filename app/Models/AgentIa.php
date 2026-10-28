<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentIa extends Model
{
    protected $table = 'agent_ia';

    protected $primaryKey = 'id_agent';

    public $timestamps = false;

    protected $fillable = [
        'nom_agent',
        'type_agent',
        'description',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'id_agent');
    }
}
