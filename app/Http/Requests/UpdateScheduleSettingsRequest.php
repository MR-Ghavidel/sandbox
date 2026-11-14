<?php

namespace App\Http\Requests;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateScheduleSettingsRequest extends FormRequest
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
            'semester_start_year' => ['required', 'integer', 'between:1300,1500'],
            'semester_start_month' => ['required', 'integer', 'between:1,12'],
            'semester_start_day' => ['required', 'integer', 'between:1,31'],
            'first_week_is_odd' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['first_week_is_odd' => 'وضعیت هفته اول'];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['semester_start_year', 'semester_start_month', 'semester_start_day'])) {
                    return;
                }

                if ($this->semesterStart() === null) {
                    $validator->errors()->add('semester_start_day', 'تاریخ شمسی وارد شده معتبر نیست.');
                }
            },
        ];
    }

    public function semesterStart(): ?CarbonImmutable
    {
        return JalaliDate::fromParts(
            (string) $this->input('semester_start_year'),
            (string) $this->input('semester_start_month'),
            (string) $this->input('semester_start_day'),
        );
    }
}
