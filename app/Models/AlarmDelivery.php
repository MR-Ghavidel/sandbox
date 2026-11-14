<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['alarm_id', 'occurs_at', 'sent_at'])]
class AlarmDelivery extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurs_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function alarm(): BelongsTo
    {
        return $this->belongsTo(Alarm::class);
    }
}
