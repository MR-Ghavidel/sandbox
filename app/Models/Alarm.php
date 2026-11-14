<?php

namespace App\Models;

use App\Support\AlarmInterval;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'alarmable_type', 'alarmable_id', 'minutes_before', 'is_active'])]
class Alarm extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minutes_before' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alarmable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AlarmDelivery::class);
    }

    /**
     * @param  Builder<Alarm>  $query
     */
    public function scopeOwnedBy(Builder $query, User|int $user): void
    {
        $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * @param  Builder<Alarm>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function label(): string
    {
        return AlarmInterval::label($this->minutes_before);
    }
}
