<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One or more monthly work-hours Excel sheets uploaded on the payroll page.
 */
class StoreExcelAttendanceImportRequest extends FormRequest
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
            'files' => ['required', 'array', 'min:1', 'max:24'],
            'files.*' => ['file', 'extensions:xlsx', 'max:5120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'حداقل یک فایل اکسل انتخاب کنید.',
            'files.*.extensions' => 'فقط فایل‌های xlsx پذیرفته می‌شوند.',
            'files.*.max' => 'حجم هر فایل باید کمتر از ۵ مگابایت باشد.',
        ];
    }
}
