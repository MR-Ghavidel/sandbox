<?php

namespace App\Http\Requests;

use App\Support\JalaliDate;
use App\Support\TimeInput;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['due_time' => TimeInput::normalize($this->input('due_time'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where('user_id', $this->user()->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date_year' => ['required', 'integer', 'between:1300,1500'],
            'due_date_month' => ['required', 'integer', 'between:1,12'],
            'due_date_day' => ['required', 'integer', 'between:1,31'],
            'due_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'عنوان کار',
            'course_id' => 'درس',
            'description' => 'توضیح',
            'due_time' => 'ساعت تحویل',
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['due_date_year', 'due_date_month', 'due_date_day'])) {
                    return;
                }

                if ($this->jalaliDate() === null) {
                    $validator->errors()->add('due_date_day', 'تاریخ شمسی وارد شده معتبر نیست.');
                }
            },
        ];
    }

    public function jalaliDate(): ?CarbonImmutable
    {
        return JalaliDate::fromParts(
            (string) $this->input('due_date_year'),
            (string) $this->input('due_date_month'),
            (string) $this->input('due_date_day'),
        );
    }

    public function dueAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->jalaliDate()->toDateString().' '.$this->validated('due_time'));
    }
}
