<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubstanceByInchikeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inchikey' => $this->route('inchikey'),
        ]);
    }

    public function rules(): array
    {
        return [
            'inchikey' => [
                'required',
                'string',
                'regex:/^[A-Z]{14}-[A-Z]{10}-[A-Z]$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'inchikey.regex' => 'The InChIKey must be in valid format (e.g., QKLPUVXBJHRFQZ-UHFFFAOYSA-N).',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
