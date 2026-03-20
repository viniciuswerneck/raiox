<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'O termo de busca é obrigatório.',
            'q.min' => 'O termo de busca deve ter pelo menos 2 caracteres.',
            'q.max' => 'O termo de busca não pode exceder 200 caracteres.',
        ];
    }
}
