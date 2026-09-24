<?php

namespace App\Http\Requests;

use App\Support\TimeInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollSettingsRequest extends FormRequest
{
    private const AMOUNT_FIELDS = ['salary', 'tax_exemption', 'advance', 'salary_divisor_days', 'overtime_multiplier', 'insurance_rate_percent', 'tax_rate_percent'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept Persian digits and thousands separators in numbers, and "8:00" style daily hours.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (self::AMOUNT_FIELDS as $field) {
            if ($this->filled($field)) {
                $normalized[$field] = str_replace([',', '٬', '،', ' '], '', TimeInput::latinDigits((string) $this->input($field)));
            }
        }

        $normalized['daily_work_time'] = TimeInput::normalize($this->input('daily_work_time'));

        $this->merge($normalized);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'salary' => ['required', 'integer', 'min:0'],
            'daily_work_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/', 'not_in:00:00'],
            'salary_divisor_days' => ['required', 'integer', 'between:1,31'],
            'overtime_multiplier' => ['required', 'numeric', 'between:0,10'],
            'insurance_rate_percent' => ['required', 'numeric', 'between:0,100'],
            'tax_rate_percent' => ['required', 'numeric', 'between:0,100'],
            'tax_exemption' => ['required', 'integer', 'min:0'],
            'advance' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'salary' => 'حقوق',
            'daily_work_time' => 'ساعت کار روزانه',
            'salary_divisor_days' => 'روزهای تقسیم حقوق',
            'overtime_multiplier' => 'ضریب اضافه‌کار',
            'insurance_rate_percent' => 'درصد بیمه',
            'tax_rate_percent' => 'درصد مالیات',
            'tax_exemption' => 'معافیت مالیاتی',
            'advance' => 'مساعده',
        ];
    }
}
