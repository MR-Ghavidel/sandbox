<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'instructor', 'location', 'color', 'notes'])]
class Course extends Model
{
    /**
     * Accent colors offered when creating a course.
     *
     * @var list<string>
     */
    public const COLORS = ['sky', 'emerald', 'amber', 'rose', 'violet', 'teal', 'orange', 'indigo'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(CourseLink::class)->orderBy('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class)->orderBy('day_of_week')->orderBy('starts_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->orderBy('due_at');
    }

    /**
     * @param  Builder<Course>  $query
     */
    public function scopeOwnedBy(Builder $query, User|int $user): void
    {
        $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * A literal hex color, used inline so Tailwind does not have to guess generated class names.
     */
    public function colorHex(): string
    {
        return match ($this->color) {
            'emerald' => '#10b981',
            'amber' => '#f59e0b',
            'rose' => '#f43f5e',
            'violet' => '#8b5cf6',
            'teal' => '#14b8a6',
            'orange' => '#f97316',
            'indigo' => '#6366f1',
            default => '#0ea5e9',
        };
    }
}
