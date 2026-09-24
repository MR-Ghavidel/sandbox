<?php

namespace Tests\Feature;

use App\Entities\PayrollMonthEntity;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\PayrollMonthRepository;
use App\Support\ExcelAttendanceReader;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsAttendanceExcelSheets;
use Tests\TestCase;

class ExcelAttendanceImportTest extends TestCase
{
    use BuildsAttendanceExcelSheets;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-24 12:00:00');
    }

    public function test_reader_reads_settings_days_times_and_notes(): void
    {
        $sheet = (new ExcelAttendanceReader)->read($this->tirSheet());

        $this->assertSame(['year' => 1405, 'month' => 4, 'salary' => 13_490_000, 'daily_work_minutes' => 420, 'salary_divisor_days' => 26, 'advance' => 500_000], $sheet['payroll_month']);

        // The "belongs to the previous month" row without times is skipped.
        $this->assertCount(3, $sheet['days']);
        $this->assertSame('2026-06-17', $sheet['days'][0]['date']); // 27 Khordad 1405
        $this->assertSame(['arrive' => '08:39', 'leave' => '16:41'], $sheet['days'][0]['pairs'][0]);
        $this->assertSame(['arrive' => '14:53', 'leave' => '18:15'], $sheet['days'][2]['pairs'][1]);
        $this->assertTrue($sheet['days'][0]['is_work_day']);
        $this->assertFalse($sheet['days'][1]['is_work_day']);
        $this->assertSame('تعطیل رسمی', $sheet['days'][1]['note']);
    }

    public function test_month_zero_in_the_farvardin_sheet_means_esfand_of_the_previous_year(): void
    {
        $path = $this->buildAttendanceSheet(1405, 1, 13_490_000, '7:00', [
            ['26/0/1405', [['08:00', '15:00']], 'Yes'],
        ]);

        $this->assertSame('2026-03-17', (new ExcelAttendanceReader)->read($path)['days'][0]['date']); // 26 Esfand 1404
    }

    public function test_the_closing_day_repeated_in_two_files_keeps_the_row_with_times(): void
    {
        $reader = new ExcelAttendanceReader;
        $tir = $reader->read($this->buildAttendanceSheet(1405, 4, 1, '7:00', [['26/4/1405', [['08:00', '16:00']], 'Yes']]));
        $mordad = $reader->read($this->buildAttendanceSheet(1405, 5, 1, '7:00', [['26/4/1405', [], 'No'], ['27/4/1405', [['09:00', '17:00']], 'Yes']]));

        $merged = $reader->merge([$tir, $mordad]);

        $this->assertCount(2, $merged['days']);
        $this->assertSame(['arrive' => '08:00', 'leave' => '16:00'], $merged['days'][0]['pairs'][0]);
        $this->assertCount(2, $merged['payroll_months']);
    }

    public function test_uploaded_sheets_are_previewed_then_applied_without_overwriting_saved_month_settings(): void
    {
        // Khordad already has saved settings; Tir has none.
        app(PayrollMonthRepository::class)->save(new PayrollMonthEntity(null, 1405, 3, 20_000_000, 480, 26, 1.4, 7, 10, 12_000_000, 0));
        $khordad = $this->buildAttendanceSheet(1405, 3, 13_490_000, '7:00', [['20/3/1405', [['08:00', '15:00']], 'Yes']]);

        $response = $this->post(route('attendance-imports.excel'), [
            'files' => [
                new UploadedFile($khordad, '09_Khordad.xlsx', null, null, true),
                new UploadedFile($this->tirSheet(), '10_Tir.xlsx', null, null, true),
            ],
        ]);

        $importId = DB::table('attendance_imports')->value('id');
        $response->assertRedirect(route('attendance-imports.show', $importId));

        $this->get(route('attendance-imports.show', $importId))
            ->assertOk()
            ->assertSee('بررسی داده‌های اکسل')
            ->assertSee('تنظیمات ماه‌ها')
            ->assertSee('از قبل ذخیره شده، تغییر نمی‌کند')
            ->assertSee('ثبت ۴ روز و تنظیمات ۱ ماه');

        $this->post(route('attendance-imports.apply', $importId))->assertRedirect();

        $months = app(PayrollMonthRepository::class);
        $this->assertSame(20_000_000, $months->find(1405, 3)->salary);
        $this->assertSame(13_490_000, $months->find(1405, 4)->salary);
        $this->assertSame(420, $months->find(1405, 4)->dailyWorkMinutes);
        $this->assertSame(500_000, $months->find(1405, 4)->advance);

        $days = app(AttendanceDayRepository::class)->getBetween(CarbonImmutable::parse('2026-06-01'), CarbonImmutable::parse('2026-07-31'));
        $this->assertSame(['arrive' => '08:39', 'leave' => '16:41'], $days['2026-06-17']->pairs[0]);
        $this->assertFalse($days['2026-06-18']->isWorkDay);
        $this->assertSame('مرخصی', $days['2026-07-17']->note);
    }

    public function test_upload_rejects_files_that_are_not_valid_sheets(): void
    {
        $this->post(route('attendance-imports.excel'), [
            'files' => [UploadedFile::fake()->create('notes.txt', 1)],
        ])->assertSessionHasErrors('files.0');

        $brokenSheet = UploadedFile::fake()->create('broken.xlsx', 1);

        $this->from(route('payroll.index'))
            ->post(route('attendance-imports.excel'), ['files' => [$brokenSheet]])
            ->assertSessionHasErrors('files');

        $this->assertDatabaseCount('attendance_imports', 0);
    }

    public function test_command_creates_an_import_and_prints_its_review_url(): void
    {
        $this->artisan('attendance:import-excel', ['files' => [$this->tirSheet()]])
            ->expectsOutputToContain('Nothing is saved yet')
            ->expectsOutputToContain(route('attendance-imports.show', 1))
            ->assertSuccessful();

        $this->assertDatabaseCount('attendance_imports', 1);
        $this->assertDatabaseCount('attendance_days', 0);
    }

    /**
     * A Tir 1405 sheet: 26 Khordad repeated from the previous month, a work day, an official holiday and a split day with leave.
     */
    private function tirSheet(): string
    {
        return $this->buildAttendanceSheet(1405, 4, 13_490_000, '7:00', [
            ['26/3/1405', [], 'No', 'این روز برای ماه قبله'],
            ['27/3/1405', [['08:39', '16:41']], 'Yes'],
            ['28/3/1405', [], 'No', 'تعطیل رسمی'],
            ['26/4/1405', [['08:43', '11:26'], ['14:53', '18:15']], 'Yes', 'مرخصی'],
        ], advance: 500_000);
    }
}
