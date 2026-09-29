<?php

namespace Tests\Feature;

use App\Entities\AttendanceDayEntity;
use App\Repositories\AttendanceDayRepository;
use App\Repositories\AttendanceImportRepository;
use App\Support\AttendanceImporter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-24 12:00:00');
    }

    public function test_extension_payload_is_stored_and_a_preview_url_is_returned_without_csrf(): void
    {
        $response = $this->postJson(route('attendance-imports.store'), $this->bizagiPayload())
            ->assertCreated()
            ->assertJsonStructure(['preview_url']);

        $import = DB::table('attendance_imports')->sole();
        $days = json_decode($import->days, true);

        $this->assertSame(route('attendance-imports.show', $import->id), $response->json('preview_url'));
        $this->assertSame(['date' => '2026-09-11', 'pairs' => [['arrive' => null, 'leave' => null]], 'leaves' => [], 'holiday_label' => 'تعطیلی جمعه'], $days[0]);
        $this->assertSame(['arrive' => '11:32', 'leave' => '17:36'], $days[1]['pairs'][0]);
        $this->assertSame(['arrive' => '08:23', 'leave' => null], $days[3]['pairs'][0]);
        $this->assertDatabaseCount('attendance_days', 0);
    }

    public function test_invalid_payload_is_rejected_with_json_errors(): void
    {
        $this->postJson(route('attendance-imports.store'), ['source' => 'bizagi', 'days' => [['date' => 'yesterday', 'pairs' => []]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('days.0.date');
    }

    public function test_preview_shows_changes_and_apply_merges_them_keeping_manual_choices(): void
    {
        // A manually marked leave day that Bizagi reports without times must stay as it is.
        app(AttendanceDayRepository::class)->saveMany([
            new AttendanceDayEntity(null, CarbonImmutable::parse('2026-09-13'), false, 'مرخصی', array_fill(0, 4, ['arrive' => null, 'leave' => null])),
        ]);
        // An existing day with a wrong time that Bizagi corrects.
        app(AttendanceDayRepository::class)->saveMany([
            new AttendanceDayEntity(null, CarbonImmutable::parse('2026-09-12'), true, null, [['arrive' => '11:30', 'leave' => '17:36'], ...array_fill(0, 3, ['arrive' => null, 'leave' => null])]),
        ]);

        $previewUrl = $this->postJson(route('attendance-imports.store'), $this->bizagiPayload())->json('preview_url');

        $this->get($previewUrl)
            ->assertOk()
            ->assertSee('بررسی داده‌های بیزاجی')
            ->assertSee('شهریور ۱۴۰۵')
            ->assertSee('مهر ۱۴۰۵')
            ->assertSee('ناقص')
            ->assertSee('ثبت ۳ روز');

        $importId = DB::table('attendance_imports')->value('id');

        $this->post(route('attendance-imports.apply', $importId))
            ->assertRedirect(route('payroll.show', ['year' => 1405, 'month' => 7]));

        $days = app(AttendanceDayRepository::class)->getBetween(CarbonImmutable::parse('2026-09-11'), CarbonImmutable::parse('2026-09-24'));

        $this->assertFalse($days['2026-09-11']->isWorkDay);
        $this->assertSame('جمعه', $days['2026-09-11']->note);
        $this->assertSame(['arrive' => '11:32', 'leave' => '17:36'], $days['2026-09-12']->pairs[0]);
        $this->assertSame('مرخصی', $days['2026-09-13']->note);
        $this->assertFalse($days['2026-09-13']->isWorkDay);
        $this->assertSame(['arrive' => '08:23', 'leave' => null], $days['2026-09-24']->pairs[0]);
        $this->assertNotNull(DB::table('attendance_imports')->value('applied_at'));

        $this->get($previewUrl)->assertSee('اعمال شده');
    }

    public function test_saved_days_are_unchanged_when_the_stored_import_has_reordered_pair_keys(): void
    {
        app(AttendanceDayRepository::class)->saveMany([
            new AttendanceDayEntity(null, CarbonImmutable::parse('2026-09-12'), true, null, [['arrive' => '11:32', 'leave' => '17:36'], ...array_fill(0, 3, ['arrive' => null, 'leave' => null])]),
        ]);

        // MySQL's JSON column stores {"arrive", "leave"} as {"leave", "arrive"}.
        $importId = app(AttendanceImportRepository::class)->create('bizagi', [
            ['date' => '2026-09-12', 'pairs' => [['leave' => '17:36', 'arrive' => '11:32']], 'leaves' => [], 'holiday_label' => null],
        ]);

        $this->assertSame(AttendanceImporter::STATUS_UNCHANGED, app(AttendanceImporter::class)->preview(app(AttendanceImportRepository::class)->findOrFail($importId))->sole()['status']);
    }

    public function test_paid_hourly_leave_counts_as_worked_time_and_unpaid_leave_only_adds_a_note(): void
    {
        // Taken from a real Bizagi grid: the leave sits in pair 1, the work before it in pair 2.
        $previewUrl = $this->postJson(route('attendance-imports.store'), [
            'source' => 'bizagi',
            'days' => [
                [
                    'date' => '1405/06/26',
                    'pairs' => [['arrive' => null, 'leave' => null], ['arrive' => '07:56', 'leave' => '13:04']],
                    'leaves' => [['label' => 'استحقاقی ساعتی', 'from' => '13:10', 'to' => '15:10']],
                    'holiday_label' => null,
                ],
                [
                    'date' => '1405/06/28',
                    'pairs' => [['arrive' => '10:00', 'leave' => '16:00']],
                    'leaves' => [['label' => 'بدون حقوق ساعتی', 'from' => '08:00', 'to' => '10:00']],
                    'holiday_label' => null,
                ],
            ],
        ])->assertCreated()->json('preview_url');

        $this->get($previewUrl)->assertOk()->assertSee('استحقاقی ساعتی 13:10–15:10');

        $this->post(route('attendance-imports.apply', DB::table('attendance_imports')->value('id')));

        $days = app(AttendanceDayRepository::class)->getBetween(CarbonImmutable::parse('2026-09-17'), CarbonImmutable::parse('2026-09-19'));

        $this->assertTrue($days['2026-09-17']->isWorkDay);
        $this->assertSame([['arrive' => '07:56', 'leave' => '13:04'], ['arrive' => '13:10', 'leave' => '15:10']], array_slice($days['2026-09-17']->pairs, 0, 2));
        $this->assertSame(7 * 60 + 8, $days['2026-09-17']->workedMinutes());
        $this->assertSame('استحقاقی ساعتی 13:10–15:10', $days['2026-09-17']->note);

        $this->assertTrue($days['2026-09-19']->isWorkDay);
        $this->assertSame(6 * 60, $days['2026-09-19']->workedMinutes());
        $this->assertSame('بدون حقوق ساعتی 08:00–10:00', $days['2026-09-19']->note);
    }

    public function test_configure_command_writes_app_url_into_the_extension(): void
    {
        $configPath = base_path('browser-extension/bizagi-export/config.js');
        $originalConfig = is_file($configPath) ? file_get_contents($configPath) : null;
        config(['app.url' => 'https://work.example.test/']);

        try {
            $this->artisan('bizagi-extension:configure')->assertSuccessful();

            $this->assertStringContainsString('export const APP_URL = "https://work.example.test";', file_get_contents($configPath));
        } finally {
            $originalConfig === null ? unlink($configPath) : file_put_contents($configPath, $originalConfig);
        }
    }

    /**
     * Shaped like the payload the Chrome extension builds from the Bizagi grid.
     *
     * @return array<string, mixed>
     */
    private function bizagiPayload(): array
    {
        $emptyPairs = [['arrive' => null, 'leave' => null]];

        return [
            'source' => 'bizagi',
            'days' => [
                ['date' => '1405/06/20', 'pairs' => $emptyPairs, 'holiday_label' => 'تعطیلی جمعه'],
                ['date' => '1405/06/21', 'pairs' => [['arrive' => '11:32', 'leave' => '17:36']], 'holiday_label' => null],
                ['date' => '1405/06/22', 'pairs' => $emptyPairs, 'holiday_label' => null],
                ['date' => '۱۴۰۵/۰۷/۰۲', 'pairs' => [['arrive' => '08:23', 'leave' => '00:00']], 'holiday_label' => null],
            ],
        ];
    }
}
