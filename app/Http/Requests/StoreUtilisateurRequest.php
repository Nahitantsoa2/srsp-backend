<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:utilisateur,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:fonctionnaire,chef_service,administrateur',
            'id_service' => 'required|exists:service,id_service',
        ];
    }
}
