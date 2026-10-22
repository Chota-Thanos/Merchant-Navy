<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'nickname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150'],
            'targetExam' => ['required', 'string', 'max:100'],
            'attemptYear' => ['required', 'integer', 'between:'.now()->year.','.(now()->year + 5)],
        ];
    }
}
