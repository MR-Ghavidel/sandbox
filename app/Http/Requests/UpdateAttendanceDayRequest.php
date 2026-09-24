<?php

namespace App\Http\Requests;

use App\Entities\AttendanceDayEntity;
use App\Support\TimeInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One day of the attendance table, auto-saved by the payroll page whenever a field changes.
 */
class UpdateAttendanceDayRequest extends FormRequest
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
        $normalized = [];

        foreach (['arrive', 'leave'] as $type) {
            $times = $this->input($type, []);

            $normalized[$type] = collect(range(1, AttendanceDayEntity::PAIRS_PER_DAY))
                ->mapWithKeys(fn (int $pair): array => [$pair => TimeInput::normalize(is_array($times) ? ($times[$pair] ?? null) : null)])
                ->all();
        }

        $this->merge($normalized);
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
            'is_work_day' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
            'arrive.*' => $time,
            'leave.*' => $time,
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
            'arrive.*.regex' => 'ساعت ورود باید به شکل ۰۸:۳۰ باشد.',
            'leave.*.regex' => 'ساعت خروج باید به شکل ۱۷:۰۰ باشد.',
        ];
    }

    /**
     * The validated day as an entity.
     */
    public function toEntity(\Carbon\CarbonImmutable $date): AttendanceDayEntity
    {
        return new AttendanceDayEntity(
            id: null,
            date: $date,
            isWorkDay: $this->boolean('is_work_day'),
            note: filled($this->validated('note')) ? trim($this->validated('note')) : null,
            pairs: array_map(fn (int $pair): array => [
                'arrive' => $this->validated('arrive')[$pair] ?? null,
                'leave' => $this->validated('leave')[$pair] ?? null,
            ], range(1, AttendanceDayEntity::PAIRS_PER_DAY)),
        );
    }
}
