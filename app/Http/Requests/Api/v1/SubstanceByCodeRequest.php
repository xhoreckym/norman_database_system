<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubstanceByCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->route('code'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^NS\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'The code must be in format NS followed by 8 digits (e.g., NS00004453).',
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

    public function getCodeWithoutPrefix(): string
    {
        return substr($this->route('code'), 2);
    }
}
