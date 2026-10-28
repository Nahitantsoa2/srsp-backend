<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_type_demarche' => 'required|exists:type_demarche,id_type_demarche',
            'details' => 'nullable|array',
            'details.nom' => 'nullable|string|max:150',
            'details.matricule' => 'nullable|string|max:50',
            'details.motif' => 'nullable|string|max:500',
        ];
    }
}
