<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'matricule' => 'required|string|max:30|unique:information,matricule,'.$id.',id_information',
            'nom' => 'required|string|max:150',
            'prenom' => 'nullable|string|max:150',
            'localite' => 'required|string|max:50',
        ];
    }
}
