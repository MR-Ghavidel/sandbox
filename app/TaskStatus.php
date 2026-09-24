<?php

namespace App;

enum TaskStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * Get the Persian label shown in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'شروع نشده',
            self::InProgress => 'در حال انجام',
            self::Completed => 'تکمیل شده',
        };
    }
}
