<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'service';

    protected $primaryKey = 'id_service';

    public $timestamps = false;

    protected $fillable = [
        'nom_service',
        'description',
    ];

    public function utilisateurs()
    {
        return $this->hasMany(Utilisateur::class, 'id_service');
    }

    public function typesDemarche()
    {
        return $this->hasMany(TypeDemarche::class, 'id_service');
    }
}
