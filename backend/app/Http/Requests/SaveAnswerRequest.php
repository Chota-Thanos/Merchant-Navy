<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnswerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'selectedOptionId' => ['nullable', 'integer'],
            'markedForReview' => ['boolean'],
            'visited' => ['boolean'],
        ];
    }
}
