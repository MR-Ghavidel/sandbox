<?php

namespace App\Entities;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * A single row of the "attendance_days" table: the arrive/leave times of one day.
 */
final readonly class AttendanceDayEntity
{
    public const PAIRS_PER_DAY = 4;

    /**
     * @param  list<array{arrive: ?string, leave: ?string}>  $pairs  Always PAIRS_PER_DAY items, times as "HH:MM".
     */
    public function __construct(
        public ?int $id,
        public CarbonImmutable $date,
        public bool $isWorkDay,
        public ?string $note,
        public array $pairs,
    ) {}

    /**
     * Build an entity from a raw database row returned by the query builder.
     *
     * @param  object{id: int|string, date: string, is_work_day: int|string|bool, note: ?string, arrive_1: ?string, leave_1: ?string, arrive_2: ?string, leave_2: ?string, arrive_3: ?string, leave_3: ?string, arrive_4: ?string, leave_4: ?string}  $row
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->id,
            date: CarbonImmutable::parse($row->date)->startOfDay(),
            isWorkDay: (bool) $row->is_work_day,
            note: $row->note,
            pairs: array_map(fn (int $pair): array => [
                'arrive' => $row->{"arrive_{$pair}"},
                'leave' => $row->{"leave_{$pair}"},
            ], range(1, self::PAIRS_PER_DAY)),
        );
    }

    /**
     * An unsaved day with no times: Fridays are off, every other day is a work day.
     */
    public static function blank(CarbonInterface $date): self
    {
        $isFriday = $date->isFriday();

        return new self(
            id: null,
            date: CarbonImmutable::parse($date)->startOfDay(),
            isWorkDay: ! $isFriday,
            note: $isFriday ? 'جمعه' : null,
            pairs: array_fill(0, self::PAIRS_PER_DAY, ['arrive' => null, 'leave' => null]),
        );
    }

    /**
     * Total minutes of all complete pairs. A leave time earlier than its arrive time is treated as past midnight.
     */
    public function workedMinutes(): int
    {
        return array_sum(array_map(function (array $pair): int {
            if ($pair['arrive'] === null || $pair['leave'] === null) {
                return 0;
            }

            $minutes = self::toMinutes($pair['leave']) - self::toMinutes($pair['arrive']);

            return $minutes < 0 ? $minutes + 24 * 60 : $minutes;
        }, $this->pairs));
    }

    /**
     * Whether a pair has only one of its two times (e.g. arrived but not left yet).
     */
    public function hasIncompletePair(): bool
    {
        foreach ($this->pairs as $pair) {
            if (($pair['arrive'] === null) !== ($pair['leave'] === null)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyTime(): bool
    {
        foreach ($this->pairs as $pair) {
            if ($pair['arrive'] !== null || $pair['leave'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert "HH:MM" to minutes since midnight.
     */
    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return (int) $hours * 60 + (int) $minutes;
    }
}
