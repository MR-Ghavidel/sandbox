<?php

namespace App\Console\Commands;

use App\Support\AttendanceImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('attendance:import-excel {files* : Paths of monthly work-hours .xlsx sheets}')]
#[Description('Read monthly work-hours Excel sheets into an import that can be reviewed and applied in the app')]
class ImportAttendanceExcel extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AttendanceImporter $importer): int
    {
        $paths = collect($this->argument('files'))->mapWithKeys(fn (string $path): array => [basename($path) => $path]);

        $missing = $paths->reject(fn (string $path): bool => is_file($path));

        if ($missing->isNotEmpty()) {
            $this->components->error('File not found: '.$missing->implode(', '));

            return self::FAILURE;
        }

        try {
            $importId = $importer->createFromExcel($paths->all());
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Read {$paths->count()} file(s). Nothing is saved yet — review and apply the import here:");
        $this->line('  '.route('attendance-imports.show', $importId));

        return self::SUCCESS;
    }
}
