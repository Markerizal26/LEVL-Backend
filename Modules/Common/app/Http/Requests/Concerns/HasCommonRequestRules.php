<?php

namespace Modules\Common\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Common\Enums\CategoryStatus;

trait HasCommonRequestRules
{
    protected function rulesCategoryStore(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:100', 'unique:categories,value'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CategoryStatus::class)],
        ];
    }

    protected function messagesCategoryStore(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'value.required' => 'Value wajib diisi.',
            'value.unique' => 'Value sudah digunakan.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus active atau inactive.',
        ];
    }

    protected function rulesCategoryUpdate(int $categoryId): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'value' => ['sometimes', 'required', 'string', 'max:100', 'unique:categories,value,'.$categoryId],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::enum(CategoryStatus::class)],
        ];
    }

    protected function messagesCategoryUpdate(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'value.required' => 'Value wajib diisi.',
            'value.unique' => 'Value sudah digunakan.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus active atau inactive.',
        ];
    }
}
