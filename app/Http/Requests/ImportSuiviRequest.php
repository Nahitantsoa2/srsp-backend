<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSuiviRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fichier' => 'required|file|mimes:xlsx,xls,csv,tsv,txt|max:10240',
        ];
    }
}
