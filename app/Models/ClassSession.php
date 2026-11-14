<?php

namespace App\Models;

use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['course_id', 'day_of_week', 'starts_at', 'ends_at', 'meets_odd_weeks', 'meets_even_weeks'])]
class ClassSession extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'meets_odd_weeks' => 'boolean',
            'meets_even_weeks' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Alarms are polymorphic, so the database foreign key cannot cascade them.
        static::deleting(fn (self $session) => $session->alarms()->delete());
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function alarms(): MorphMany
    {
        return $this->morphMany(Alarm::class, 'alarmable')->orderBy('minutes_before');
    }

    /**
     * @param  Builder<ClassSession>  $query
     */
    public function scopeOwnedBy(Builder $query, User|int $user): void
    {
        $query->whereHas('course', fn (Builder $course) => $course->ownedBy($user));
    }

    /**
     * @param  Builder<ClassSession>  $query
     */
    public function scopeForParity(Builder $query, bool $isOddWeek): void
    {
        $query->where($isOddWeek ? 'meets_odd_weeks' : 'meets_even_weeks', true);
    }

    public function meetsParity(bool $isOddWeek): bool
    {
        return $isOddWeek ? $this->meets_odd_weeks : $this->meets_even_weeks;
    }

    public function dayName(): string
    {
        return JalaliDate::weekdayName($this->day_of_week);
    }

    public function startTime(): string
    {
        return substr((string) $this->starts_at, 0, 5);
    }

    public function endTime(): ?string
    {
        return $this->ends_at === null ? null : substr((string) $this->ends_at, 0, 5);
    }

    public function timeRange(): string
    {
        return $this->endTime() === null
            ? $this->startTime()
            : $this->startTime().' تا '.$this->endTime();
    }

    public function weekLabel(): string
    {
        return match (true) {
            $this->meets_odd_weeks && $this->meets_even_weeks => 'هر هفته',
            $this->meets_odd_weeks => 'فقط هفته‌های فرد',
            default => 'فقط هفته‌های زوج',
        };
    }

    public function summary(): string
    {
        return $this->dayName().'، '.$this->timeRange().' ('.$this->weekLabel().')';
    }
}
