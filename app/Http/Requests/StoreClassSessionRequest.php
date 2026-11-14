<?php

namespace App\Http\Requests;

use App\Support\TimeInput;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'starts_at' => TimeInput::normalize($this->input('starts_at')),
            'ends_at' => TimeInput::normalize($this->input('ends_at')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'meets_odd_weeks' => ['sometimes', 'boolean'],
            'meets_even_weeks' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'day_of_week' => 'روز هفته',
            'starts_at' => 'ساعت شروع',
            'ends_at' => 'ساعت پایان',
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('meets_odd_weeks') && ! $this->boolean('meets_even_weeks')) {
                    $validator->errors()->add('meets_odd_weeks', 'کلاس باید حداقل در هفته‌های فرد یا زوج برگزار شود.');
                }

                $startsAt = $this->input('starts_at');
                $endsAt = $this->input('ends_at');

                if (is_string($startsAt) && is_string($endsAt) && $endsAt <= $startsAt) {
                    $validator->errors()->add('ends_at', 'ساعت پایان باید بعد از ساعت شروع باشد.');
                }
            },
        ];
    }
}
