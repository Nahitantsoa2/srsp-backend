<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_service' => 'required|string|max:150|unique:service,nom_service',
            'description' => 'nullable|string',
        ];
    }
}
