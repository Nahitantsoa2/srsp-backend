<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matricule' => 'required|string|max:30|unique:information,matricule',
            'nom' => 'required|string|max:150',
            'prenom' => 'nullable|string|max:150',
            'localite' => 'required|string|max:50',
        ];
    }
}
