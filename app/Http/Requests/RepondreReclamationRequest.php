<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RepondreReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reponse' => 'required|string',
            'statut' => 'required|in:repondue,cloturee',
        ];
    }
}
