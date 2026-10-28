<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupprimerMessageReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|in:moi,tous',
        ];
    }
}
