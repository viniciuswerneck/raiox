<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cep' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'cep.required' => 'O campo CEP é obrigatório.',
            'cep.min' => 'O CEP deve ter pelo menos 3 caracteres.',
            'cep.max' => 'O CEP não pode exceder 100 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cep')) {
            $this->merge([
                'cep' => trim($this->input('cep')),
            ]);
        }
    }
}
