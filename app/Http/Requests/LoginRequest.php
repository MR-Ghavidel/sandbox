<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'ایمیل را وارد کنید.',
            'email.string' => 'ایمیل معتبر وارد کنید.',
            'email.email' => 'ایمیل معتبر وارد کنید.',
            'email.max' => 'ایمیل نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.string' => 'رمز عبور معتبر وارد کنید.',
            'remember.boolean' => 'گزینه مرا به خاطر بسپار معتبر نیست.',
        ];
    }
}
