<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmpodatSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search_type' => $this->route('search_type'),
            'search_value' => $this->route('search_value'),
        ]);
    }

    public function rules(): array
    {
        // Note: search_type and search_value are validated in withValidator()
        // They are URL params, not query params, so not listed here to avoid Scribe confusion
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Scribe: Define URL parameters
     */
    public function urlParameters(): array
    {
        return [
            'search_type' => [
                'description' => 'The type of search.',
                'required' => true,
                'example' => 'substance',
            ],
            'search_value' => [
                'description' => 'For substance: NORMAN ID (e.g., NS00000214) or InChIKey. For country: 2-letter ISO code (e.g., SK, DE).',
                'required' => true,
                'example' => 'NS00000214',
            ],
        ];
    }

    /**
     * Scribe: Define query parameters
     */
    public function queryParameters(): array
    {
        return [
            'page' => [
                'description' => 'The page number for pagination.',
                'required' => false,
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Number of records per page (max 1000, default 100).',
                'required' => false,
                'example' => 100,
            ],
        ];
    }

    /**
     * Scribe: No body parameters for GET request
     */
    public function bodyParameters(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $searchType = $this->route('search_type');
            $searchValue = $this->route('search_value');

            // Validate search_type (URL param)
            if (! in_array($searchType, ['substance', 'country'])) {
                $validator->errors()->add(
                    'search_type',
                    'Search type must be either "substance" or "country".'
                );

                return;
            }

            // Validate search_value based on search_type
            if ($searchType === 'substance') {
                $isNsCode = preg_match('/^NS\d{8}$/', $searchValue);
                $isInchikey = preg_match('/^[A-Z]{14}-[A-Z]{10}-[A-Z]$/', $searchValue);

                if (! $isNsCode && ! $isInchikey) {
                    $validator->errors()->add(
                        'search_value',
                        'Substance must be a valid NORMAN ID (NS followed by 8 digits, e.g., NS00004453) or InChIKey (e.g., QKLPUVXBJHRFQZ-UHFFFAOYSA-N).'
                    );
                }
            }

            if ($searchType === 'country') {
                if (! preg_match('/^[A-Z]{2}$/', strtoupper($searchValue))) {
                    $validator->errors()->add(
                        'search_value',
                        'Country must be a valid 2-letter ISO country code (e.g., SK, DE, FR).'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'search_type.in' => 'Search type must be either "substance" or "country".',
            'per_page.max' => 'Maximum allowed per_page is 1000 records.',
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

    public function getSearchType(): string
    {
        return $this->route('search_type');
    }

    public function getSearchValue(): string
    {
        return $this->route('search_value');
    }

    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 100);
    }

    public function isSubstanceNsCode(): bool
    {
        return preg_match('/^NS\d{8}$/', $this->route('search_value')) === 1;
    }

    public function isSubstanceInchikey(): bool
    {
        return preg_match('/^[A-Z]{14}-[A-Z]{10}-[A-Z]$/', $this->route('search_value')) === 1;
    }

    public function getSubstanceCodeWithoutPrefix(): ?string
    {
        if ($this->isSubstanceNsCode()) {
            return substr($this->route('search_value'), 2);
        }

        return null;
    }
}
