<?php

namespace App\Http\Requests;

use App\Entities\AttendanceDayEntity;
use App\Support\TimeInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceDaysRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize typed times ("8:5", "۰۸:۳۰", "830") to "HH:MM" before validating.
     */
    protected function prepareForValidation(): void
    {
        $days = collect($this->input('days', []))->map(function (mixed $day): mixed {
            if (! is_array($day)) {
                return $day;
            }

            foreach (['arrive', 'leave'] as $type) {
                $day[$type] = collect(range(1, AttendanceDayEntity::PAIRS_PER_DAY))
                    ->mapWithKeys(fn (int $pair): array => [$pair => TimeInput::normalize($day[$type][$pair] ?? null)])
                    ->all();
            }

            return $day;
        });

        $this->merge(['days' => $days->all()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $time = ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'];

        return [
            'days' => ['required', 'array'],
            'days.*.is_work_day' => ['required', 'boolean'],
            'days.*.note' => ['nullable', 'string', 'max:255'],
            'days.*.arrive.*' => $time,
            'days.*.leave.*' => $time,
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
            'days.*.arrive.*.regex' => 'ساعت ورود باید به شکل ۰۸:۳۰ باشد.',
            'days.*.leave.*.regex' => 'ساعت خروج باید به شکل ۱۷:۰۰ باشد.',
        ];
    }
}
