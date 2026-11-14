<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'chat_id', 'first_name', 'username', 'state', 'context', 'linked_at'])]
class BaleSubscriber extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'linked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clearState(): void
    {
        $this->update(['state' => null, 'context' => null]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function startState(string $state, array $context = []): void
    {
        $this->update(['state' => $state, 'context' => $context]);
    }
}
