<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nom_service' => 'sometimes|string|max:150|unique:service,nom_service,'.$id.',id_service',
            'description' => 'nullable|string',
        ];
    }
}
