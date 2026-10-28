<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeDemarche extends Model
{
    protected $table = 'type_demarche';

    protected $primaryKey = 'id_type_demarche';

    public $timestamps = false;

    protected $fillable = [
        'libelle',
        'description',
        'id_service',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'id_service');
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class, 'id_type_demarche');
    }
}
