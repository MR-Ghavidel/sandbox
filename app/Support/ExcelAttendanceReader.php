<?php

namespace App\Support;

use App\Entities\AttendanceDayEntity;
use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Reads the personal monthly work-hours Excel sheet (e.g. "11_Mordad.xlsx") without any spreadsheet library.
 *
 * Layout of the sheet:
 * - C2 year, C3 month, C4 daily work hours (time), I2 salary divisor days, I3 salary, G8 advance ("mosaede").
 * - From row 13: A date ("27/4/1405", "26/0/1405" means 26 Esfand of the previous year),
 *   B–G three arrive/leave pairs (times), I "Yes"/"No" work day, K note.
 *
 * Excel stores formula results next to the formula, so the cached values are read as they are.
 */
class ExcelAttendanceReader
{
    private const FIRST_DAY_ROW = 13;

    private const PAIR_COLUMNS = [['B', 'C'], ['D', 'E'], ['F', 'G']];

    /**
     * A row that only repeats the previous month's closing day, without data of its own.
     */
    private const PREVIOUS_MONTH_NOTE = 'این روز برای ماه قبله';

    /**
     * @return array{
     *     days: list<array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: null, is_work_day: bool, note: ?string}>,
     *     payroll_month: array{year: int, month: int, salary: int, daily_work_minutes: int, salary_divisor_days: int, advance: int}
     * }
     */
    public function read(string $path): array
    {
        $cells = $this->readFirstSheet($path);

        $year = (int) ($cells['C2'] ?? 0);
        $month = (int) ($cells['C3'] ?? 0);

        if ($year < 1300 || $month < 1 || $month > 12) {
            throw new InvalidArgumentException('سال یا ماه در خانه‌های C2 و C3 پیدا نشد.');
        }

        $days = [];

        for ($row = self::FIRST_DAY_ROW; isset($cells["A{$row}"]) && trim((string) $cells["A{$row}"]) !== ''; $row++) {
            $day = $this->readDay($cells, $row);

            if ($day !== null) {
                $days[] = $day;
            }
        }

        return [
            'days' => $days,
            'payroll_month' => [
                'year' => $year,
                'month' => $month,
                'salary' => (int) round((float) ($cells['I3'] ?? 0)),
                'daily_work_minutes' => $this->toMinutes($cells['C4'] ?? null) ?? 8 * 60,
                'salary_divisor_days' => (int) ($cells['I2'] ?? 26) ?: 26,
                'advance' => (int) round((float) ($cells['G8'] ?? 0)),
            ],
        ];
    }

    /**
     * Merge several read sheets: the closing day of a month appears in two files, so for a repeated
     * date the row that has times wins, then the row of the later file.
     *
     * @param  list<array{days: list<array<string, mixed>>, payroll_month: array<string, int>}>  $sheets
     * @return array{days: list<array<string, mixed>>, payroll_months: list<array<string, int>>}
     */
    public function merge(array $sheets): array
    {
        $daysByDate = [];

        foreach ($sheets as $sheet) {
            foreach ($sheet['days'] as $day) {
                $existing = $daysByDate[$day['date']] ?? null;

                if ($existing === null || ! $this->hasTimes($existing) || $this->hasTimes($day)) {
                    $daysByDate[$day['date']] = $day;
                }
            }
        }

        ksort($daysByDate);

        return [
            'days' => array_values($daysByDate),
            'payroll_months' => collect($sheets)->pluck('payroll_month')->unique(fn (array $month): string => $month['year'].'-'.$month['month'])->values()->all(),
        ];
    }

    /**
     * @param  array<string, string>  $cells
     * @return array{date: string, pairs: list<array{arrive: ?string, leave: ?string}>, holiday_label: null, is_work_day: bool, note: ?string}|null
     */
    private function readDay(array $cells, int $row): ?array
    {
        if (! preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim(TimeInput::latinDigits((string) $cells["A{$row}"])), $matches)) {
            return null;
        }

        [, $dayOfMonth, $jalaliMonth, $jalaliYear] = array_map('intval', $matches);

        if ($jalaliMonth === 0) {
            [$jalaliYear, $jalaliMonth] = [$jalaliYear - 1, 12];
        }

        $pairs = array_map(fn (array $columns): array => [
            'arrive' => $this->toTime($cells[$columns[0].$row] ?? null),
            'leave' => $this->toTime($cells[$columns[1].$row] ?? null),
        ], self::PAIR_COLUMNS);

        $note = trim((string) ($cells["K{$row}"] ?? '')) ?: null;
        $day = [
            'date' => JalaliDate::toGregorian($jalaliYear, $jalaliMonth, $dayOfMonth)->toDateString(),
            'pairs' => array_pad($pairs, AttendanceDayEntity::PAIRS_PER_DAY, ['arrive' => null, 'leave' => null]),
            'holiday_label' => null,
            'is_work_day' => strcasecmp(trim((string) ($cells["I{$row}"] ?? '')), 'yes') === 0,
            'note' => $note,
        ];

        return $note === self::PREVIOUS_MONTH_NOTE && ! $this->hasTimes($day) ? null : $day;
    }

    /**
     * @param  array{pairs: list<array{arrive: ?string, leave: ?string}>}  $day
     */
    private function hasTimes(array $day): bool
    {
        foreach ($day['pairs'] as $pair) {
            if ($pair['arrive'] !== null || $pair['leave'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Excel stores a time as a fraction of a day (0.5 = 12:00).
     */
    private function toMinutes(?string $value): ?int
    {
        if ($value === null || ! is_numeric($value)) {
            return null;
        }

        $minutes = (int) round(fmod((float) $value, 1) * 24 * 60);

        return $minutes > 0 ? $minutes : null;
    }

    private function toTime(?string $value): ?string
    {
        $minutes = $this->toMinutes($value);

        return $minutes === null ? null : sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
    }

    /**
     * Read every cell of the workbook's first sheet as its displayed raw value, keyed by reference ("B14").
     *
     * @return array<string, string>
     */
    private function readFirstSheet(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new InvalidArgumentException('فایل اکسل (xlsx) معتبر نیست.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetXml = $zip->getFromName($this->firstSheetPath($zip));

            if ($sheetXml === false) {
                throw new InvalidArgumentException('برگه‌ای در فایل اکسل پیدا نشد.');
            }

            $sheet = new SimpleXMLElement($sheetXml);
            $cells = [];

            foreach ($sheet->sheetData->row as $row) {
                foreach ($row->c as $cell) {
                    $type = (string) $cell['t'];
                    $value = match ($type) {
                        's' => $sharedStrings[(int) $cell->v] ?? '',
                        'inlineStr' => $this->textOf($cell->is),
                        default => (string) $cell->v,
                    };

                    $cells[(string) $cell['r']] = $value;
                }
            }

            return $cells;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = [];

        foreach ((new SimpleXMLElement($xml))->si as $item) {
            $strings[] = $this->textOf($item);
        }

        return $strings;
    }

    /**
     * Text of a string item, which is either a single <t> or several rich-text runs (<r><t>).
     */
    private function textOf(SimpleXMLElement $item): string
    {
        if (isset($item->t)) {
            return (string) $item->t;
        }

        $text = '';

        foreach ($item->r as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = new SimpleXMLElement($zip->getFromName('xl/workbook.xml'));
        $relationships = new SimpleXMLElement($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $firstSheetRelationshipId = (string) $workbook->sheets->sheet[0]->attributes('r', true)['id'];

        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $firstSheetRelationshipId) {
                $target = ltrim((string) $relationship['Target'], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }
}
