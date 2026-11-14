<?php

namespace App\Models;

use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Number;

#[Fillable(['user_id', 'course_id', 'title', 'description', 'due_at', 'is_done'])]
class Assignment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'is_done' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Alarms are polymorphic, so the database foreign key cannot cascade them.
        static::deleting(fn (self $assignment) => $assignment->alarms()->delete());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * @param  Builder<Assignment>  $query
     */
    public function scopeOwnedBy(Builder $query, User|int $user): void
    {
        $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * @param  Builder<Assignment>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('is_done', false);
    }

    public function isOverdue(): bool
    {
        return ! $this->is_done && $this->due_at->isPast();
    }

    public function dueLabel(): string
    {
        return JalaliDate::long($this->due_at).'، ساعت '.JalaliDate::time($this->due_at);
    }

    /**
     * A human countdown such as "۳ روز مانده" or "۲ ساعت گذشته".
     */
    public function countdown(): string
    {
        $reference = CarbonImmutable::now($this->due_at->getTimezone());
        $isFuture = $this->due_at->isFuture();
        $diff = $isFuture ? $reference->diff($this->due_at) : $this->due_at->diff($reference);

        $value = match (true) {
            $diff->d >= 1 => Number::format($diff->d, locale: 'fa').' روز',
            $diff->h >= 1 => Number::format($diff->h, locale: 'fa').' ساعت',
            $diff->i >= 1 => Number::format($diff->i, locale: 'fa').' دقیقه',
            default => 'چند لحظه',
        };

        if (! $isFuture) {
            return $value.' گذشته';
        }

        return $value.' مانده';
    }
}
