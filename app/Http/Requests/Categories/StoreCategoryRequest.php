<?php

namespace App\Http\Requests\Categories;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $validator->errors()->has('name') && $this->name) {
                $slug = Str::slug($this->name);
                if (\App\Models\Category::where('slug', $slug)->exists()) {
                    $validator->errors()->add('name', 'A category with a similar slug already exists.');
                }
            }
        });
    }
}
