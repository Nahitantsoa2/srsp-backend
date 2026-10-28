<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTypeDemarcheRequest;
use App\Models\TypeDemarche;

class TypeDemarcheController extends Controller
{
    // GET /api/types-demarche — liste utilisée pour peupler le formulaire manuel
    public function index()
    {
        return TypeDemarche::orderBy('libelle')->get();
    }

    // POST /api/admin/types-demarche — réservé à l'administrateur
    public function store(StoreTypeDemarcheRequest $request)
    {
        return response()->json(TypeDemarche::create($request->validated()), 201);
    }
}
