<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUtilisateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:utilisateur,email,'.$id.',id_utilisateur',
            'role' => 'sometimes|in:fonctionnaire,chef_service,administrateur',
            'id_service' => 'sometimes|exists:service,id_service',
        ];
    }
}
