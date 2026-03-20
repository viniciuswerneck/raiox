<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompareCepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cepA' => ['required', 'string', 'regex:/^[0-9]{5}-?[0-9]{3}$|^[0-9]{8}$/'],
            'cepB' => ['required', 'string', 'regex:/^[0-9]{5}-?[0-9]{3}$|^[0-9]{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'cepA.required' => 'O primeiro CEP é obrigatório.',
            'cepA.regex' => 'O primeiro CEP informado é inválido.',
            'cepB.required' => 'O segundo CEP é obrigatório.',
            'cepB.regex' => 'O segundo CEP informado é inválido.',
        ];
    }

    public function getCepA(): string
    {
        return preg_replace('/\D/', '', $this->input('cepA'));
    }

    public function getCepB(): string
    {
        return preg_replace('/\D/', '', $this->input('cepB'));
    }
}
