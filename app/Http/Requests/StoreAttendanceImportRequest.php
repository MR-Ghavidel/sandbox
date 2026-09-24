<?php

namespace App\Http\Requests;

use App\Entities\AttendanceDayEntity;
use App\Support\JalaliDate;
use App\Support\TimeInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Days sent by the Bizagi Chrome extension. Dates are Jalali ("1405/06/21"), times are "HH:MM".
 */
class StoreAttendanceImportRequest extends FormRequest
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
            'source' => ['required', 'string', 'max:50'],
            'days' => ['required', 'array', 'min:1', 'max:62'],
            'days.*.date' => ['required', 'string', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/'],
            'days.*.pairs' => ['present', 'array', 'max:'.AttendanceDayEntity::PAIRS_PER_DAY],
            'days.*.pairs.*.arrive' => ['nullable', 'string', 'max:20'],
            'days.*.pairs.*.leave' => ['nullable', 'string', 'max:20'],
            'days.*.holiday_label' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Normalize digits before validating the Jalali dates.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'days' => collect($this->input('days', []))
                ->map(fn (mixed $day): mixed => is_array($day) && isset($day['date'])
                    ? [...$day, 'date' => TimeInput::latinDigits(trim((string) $day['date']))]
                    : $day)
                ->all(),
        ]);
    }

    /**
     * The received days converted to Gregorian dates with normalized times.
     * Values that are not times (e.g. "تعطیلی جمعه") are ignored.
     *
     * @return list<array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: ?string}>
     */
    public function normalizedDays(): array
    {
        $timeOrNull = function (?string $value): ?string {
            $time = TimeInput::normalize($value);

            return $time !== null && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : null;
        };

        return collect($this->validated('days'))
            ->map(function (array $day) use ($timeOrNull): array {
                [$year, $month, $dayOfMonth] = array_map('intval', explode('/', $day['date']));

                return [
                    'date' => JalaliDate::toGregorian($year, $month, $dayOfMonth)->toDateString(),
                    'pairs' => collect($day['pairs'])->map(fn (array $pair): array => [
                        'arrive' => $timeOrNull($pair['arrive'] ?? null),
                        'leave' => $timeOrNull($pair['leave'] ?? null),
                    ])->values()->all(),
                    'holiday_label' => filled($day['holiday_label'] ?? null) ? trim($day['holiday_label']) : null,
                ];
            })
            ->unique('date')
            ->values()
            ->all();
    }
}
