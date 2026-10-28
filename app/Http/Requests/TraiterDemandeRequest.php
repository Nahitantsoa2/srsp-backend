<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraiterDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => 'required|in:validee,rejetee,complement_demande',
        ];
    }
}
