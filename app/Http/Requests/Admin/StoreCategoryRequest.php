<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'parent_id'   => ['nullable', 'uuid', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ];
    }
}