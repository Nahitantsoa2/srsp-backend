<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeDemarcheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => 'required|string|max:150',
            'description' => 'nullable|string',
            'id_service' => 'required|exists:service,id_service',
        ];
    }
}
