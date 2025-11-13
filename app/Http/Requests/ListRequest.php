<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:50', 'regex:/^-?[a-z_]+$/i'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.array' => 'Filter harus berupa array.',
            'filter.*.string' => 'Setiap nilai filter harus berupa string.',
            'sort.regex' => 'Format sort tidak valid. Gunakan format: field_name atau -field_name untuk descending.',
            'page.integer' => 'Halaman harus berupa angka.',
            'page.min' => 'Halaman minimal 1.',
            'per_page.integer' => 'Per page harus berupa angka.',
            'per_page.min' => 'Per page minimal 1.',
            'per_page.max' => 'Per page maksimal 100.',
            'search.string' => 'Pencarian harus berupa string.',
        ];
    }

    public function attributes(): array
    {
        return [
            'filter' => 'filter',
            'sort' => 'sort',
            'page' => 'page',
            'per_page' => 'per_page',
            'search' => 'search',
        ];
    }
}
