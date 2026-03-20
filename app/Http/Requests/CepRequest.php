<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cep' => ['required', 'string', 'regex:/^[0-9]{5}-?[0-9]{3}$|^[0-9]{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'cep.required' => 'O CEP é obrigatório.',
            'cep.regex' => 'O CEP informado é inválido.',
        ];
    }

    public function getCleanCep(): string
    {
        return preg_replace('/\D/', '', $this->input('cep'));
    }
}
