<?php

namespace Tests\Concerns;

use ZipArchive;

/**
 * Builds small .xlsx files shaped like the personal monthly work-hours sheet, for tests.
 */
trait BuildsAttendanceExcelSheets
{
    /**
     * @param  list<array{0: string, 1: list<array{0: string, 1: string}>, 2: string, 3?: string}>  $days  [date "d/m/Y", pairs, "Yes"|"No", note]
     */
    protected function buildAttendanceSheet(int $year, int $month, int $salary, string $dailyHours, array $days, int $advance = 0): string
    {
        $sharedStrings = [];
        $shared = function (string $text) use (&$sharedStrings): int {
            $index = array_search($text, $sharedStrings, true);

            if ($index === false) {
                $sharedStrings[] = $text;
                $index = count($sharedStrings) - 1;
            }

            return $index;
        };

        [$hours, $minutes] = explode(':', $dailyHours);
        $cells = [
            'C2' => ['n', $year],
            'C3' => ['s', $shared((string) $month)],
            'C4' => ['n', ((int) $hours * 60 + (int) $minutes) / 1440],
            'I2' => ['n', 26],
            'I3' => ['n', $salary],
            'G8' => ['n', $advance],
        ];

        foreach (array_values($days) as $index => $day) {
            $row = 13 + $index;
            $cells["A{$row}"] = ['str', $day[0]]; // Cached result of the date formula.

            foreach (['B', 'C', 'D', 'E', 'F', 'G'] as $columnIndex => $column) {
                $time = $day[1][intdiv($columnIndex, 2)][$columnIndex % 2] ?? '00:00';
                [$hours, $minutes] = explode(':', $time);
                $cells["{$column}{$row}"] = ['n', ((int) $hours * 60 + (int) $minutes) / 1440];
            }

            $cells["I{$row}"] = ['s', $shared($day[2])];

            if (isset($day[3])) {
                $cells["K{$row}"] = ['s', $shared($day[3])];
            }
        }

        $rowsXml = collect($cells)
            ->groupBy(fn (array $cell, string $reference): int => (int) preg_replace('/\D/', '', $reference), preserveKeys: true)
            ->sortKeys()
            ->map(fn ($rowCells, int $row): string => "<row r=\"{$row}\">".$rowCells->map(fn (array $cell, string $reference): string => match ($cell[0]) {
                's' => "<c r=\"{$reference}\" t=\"s\"><v>{$cell[1]}</v></c>",
                'str' => "<c r=\"{$reference}\" t=\"str\"><f>formula</f><v>{$cell[1]}</v></c>",
                default => "<c r=\"{$reference}\"><v>{$cell[1]}</v></c>",
            })->implode('').'</row>')
            ->implode('');

        $path = tempnam(sys_get_temp_dir(), 'attendance').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet3" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.collect($sharedStrings)->map(fn (string $text): string => '<si><t>'.htmlspecialchars($text, ENT_XML1).'</t></si>')->implode('').'</sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$rowsXml.'</sheetData></worksheet>');
        $zip->close();

        return $path;
    }
}
