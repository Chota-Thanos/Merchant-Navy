<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'code' => ['required', 'digits:'.config('otp.length')],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
