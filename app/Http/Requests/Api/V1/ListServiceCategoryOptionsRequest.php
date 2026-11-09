<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListServiceCategoryOptionsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('active')) {
            $active = filter_var($this->input('active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($active !== null) {
                $this->merge(['active' => $active]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['active' => ['sometimes', 'boolean']];
    }
}
