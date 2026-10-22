<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // The browser's last known answers, keyed by question id (optional safety net)
            'answers' => ['nullable', 'array', 'max:200'],
            'answers.*.selectedOptionId' => ['nullable', 'integer'],
            'answers.*.markedForReview' => ['boolean'],
            'answers.*.visited' => ['boolean'],
        ];
    }
}
