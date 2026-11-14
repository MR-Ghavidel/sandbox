<?php

namespace App\Services\Bale;

use App\Models\Alarm;
use App\Models\Assignment;
use App\Models\BaleLinkCode;
use App\Models\BaleSubscriber;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\User;
use App\Services\ScheduleService;
use App\Support\AlarmInterval;
use App\Support\JalaliDate;
use App\Support\TimeInput;
use Carbon\CarbonImmutable;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * The Persian Bale bot: schedule, course links, alarms and assignments.
 *
 * The bot uses the same database as the website, so alarms created here are
 * delivered by the scheduler and alarms created on the website are editable here.
 */
class BaleBot
{
    /**
     * @var list<string>
     */
    private const MENU_LABELS = [
        '📅 برنامه هفته',
        '📚 درس‌ها',
        '📝 کارها',
        '⏰ یادآورها',
        '⚙️ تنظیمات',
        '❓ راهنما',
    ];

    public function __construct(private BaleClient $bale, private ScheduleService $schedule) {}

    /**
     * @param  array<string, mixed>  $update
     */
    public function handleUpdate(array $update): void
    {
        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        if (isset($update['message']) && is_array($update['message'])) {
            $this->handleMessage($update['message']);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $subscriber = BaleSubscriber::with('user')->where('chat_id', $chatId)->first();

        if (preg_match('/^\/start(?:@\w+)?\s*(.*)$/u', $text, $matches)) {
            $this->handleStart($chatId, $message, trim($matches[1]));

            return;
        }

        if (preg_match('/^\/link\s+(.+)$/u', $text, $matches)) {
            $this->linkAccount($chatId, $message, trim($matches[1]));

            return;
        }

        if ($subscriber === null) {
            $this->send($chatId, "برای استفاده از ربات ابتدا حساب خود را وصل کنید:\n\n"
                ."۱. در سایت به «تنظیمات» بروید.\n"
                .'۲. کد اتصال بگیرید.'."\n"
                .'۳. کد را به این شکل بفرستید: <code>/start CODE</code>');

            return;
        }

        if ($text === 'لغو' || $text === '/cancel') {
            $subscriber->clearState();
            $this->send($chatId, 'لغو شد.', $this->mainKeyboard());

            return;
        }

        if ($subscriber->state !== null) {
            // A menu label (or a command) leaves the current step; other text is the step's answer.
            if (! in_array($text, self::MENU_LABELS, true) && ! str_starts_with($text, '/')) {
                $this->handleState($subscriber, $text);

                return;
            }

            $subscriber->clearState();
        }

        $intent = $this->intentFor($text);

        if ($intent === null) {
            $this->send($chatId, $this->helpText(), $this->mainKeyboard());

            return;
        }

        $this->runIntent($subscriber, $intent);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleStart(string $chatId, array $message, string $payload): void
    {
        if ($payload !== '') {
            $this->linkAccount($chatId, $message, $payload);

            return;
        }

        $subscriber = BaleSubscriber::with('user')->where('chat_id', $chatId)->first();

        if ($subscriber === null) {
            $this->send($chatId, "سلام 👋\nبرای استفاده از ربات ابتدا حساب خود را وصل کنید:\n\n"
                ."۱. در سایت به «تنظیمات» بروید.\n"
                ."۲. کد اتصال بگیرید.\n"
                .'۳. کد را به این شکل بفرستید: <code>/start CODE</code>');

            return;
        }

        $this->send($chatId, 'سلام '.e($subscriber->user->name)." 👋\nبه ربات برنامه دانشگاه خوش آمدید.\n\n".$this->helpText(), $this->mainKeyboard());
        $this->showSchedule($subscriber);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function linkAccount(string $chatId, array $message, string $payload): void
    {
        $code = BaleLinkCode::query()
            ->where('code', Str::upper($payload))
            ->where('expires_at', '>', now())
            ->first();

        if ($code === null) {
            $this->send($chatId, '❌ کد اتصال معتبر نیست یا منقضی شده است. از تنظیمات سایت کد تازه بگیرید.');

            return;
        }

        BaleSubscriber::where('user_id', $code->user_id)->where('chat_id', '!=', $chatId)->delete();

        $subscriber = BaleSubscriber::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'user_id' => $code->user_id,
                'first_name' => $message['from']['first_name'] ?? null,
                'username' => $message['from']['username'] ?? null,
                'linked_at' => now(),
                'state' => null,
                'context' => null,
            ],
        );

        $code->delete();

        $this->send($chatId, "✅ حساب شما با موفقیت به ربات وصل شد.\n\n".$this->helpText(), $this->mainKeyboard());
        $this->showSchedule($subscriber->load('user'));
    }

    private function intentFor(string $text): ?string
    {
        return match (true) {
            $text === '/schedule' || $text === '📅 برنامه هفته' || str_contains($text, 'برنامه') => 'schedule',
            $text === '/courses' || $text === '📚 درس‌ها' || str_contains($text, 'درس') => 'courses',
            $text === '/assignments' || $text === '/tasks' || $text === '📝 کارها' || str_contains($text, 'کار') => 'assignments',
            $text === '/alarms' || $text === '⏰ یادآورها' || str_contains($text, 'یادآور') => 'alarms',
            $text === '/settings' || $text === '⚙️ تنظیمات' || str_contains($text, 'تنظیم') => 'settings',
            $text === '/help' || $text === '❓ راهنما' || str_contains($text, 'راهنما') => 'help',
            default => null,
        };
    }

    private function runIntent(BaleSubscriber $subscriber, string $intent): void
    {
        match ($intent) {
            'schedule' => $this->showSchedule($subscriber),
            'courses' => $this->showCourses($subscriber),
            'assignments' => $this->showAssignments($subscriber),
            'alarms' => $this->showAllAlarms($subscriber),
            'settings' => $this->showSettings($subscriber),
            default => $this->send($subscriber->chat_id, $this->helpText(), $this->mainKeyboard()),
        };
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function handleCallback(array $query): void
    {
        $this->bale->answerCallbackQuery((string) ($query['id'] ?? ''));

        $chatId = (string) ($query['message']['chat']['id'] ?? '');
        $data = (string) ($query['data'] ?? '');
        $subscriber = BaleSubscriber::with('user')->where('chat_id', $chatId)->first();

        if ($subscriber === null) {
            $this->send($chatId, 'ابتدا حساب خود را با کد اتصال وصل کنید.');

            return;
        }

        [$action, $rest] = array_pad(explode(':', $data, 2), 2, '');
        $args = $rest === '' ? [] : explode(':', $rest);

        match ($action) {
            'menu' => $this->runIntent($subscriber, $args[0] ?? 'help'),
            'course' => $this->showCourse($subscriber, (int) ($args[0] ?? 0)),
            'course_alarms' => $this->showCourseAlarms($subscriber, (int) ($args[0] ?? 0)),
            'session' => $this->showSession($subscriber, (int) ($args[0] ?? 0)),
            'alarm_add' => $this->showAlarmPresets($subscriber, (string) ($args[0] ?? ''), (int) ($args[1] ?? 0), 'create'),
            'alarm_set' => $this->createAlarm($subscriber, (string) ($args[0] ?? ''), (int) ($args[1] ?? 0), (int) ($args[2] ?? -1)),
            'alarm_edit' => $this->showEditPresets($subscriber, (int) ($args[0] ?? 0)),
            'alarm_upd' => $this->updateAlarm($subscriber, (int) ($args[0] ?? 0), (int) ($args[1] ?? -1)),
            'alarm_del' => $this->deleteAlarm($subscriber, (int) ($args[0] ?? 0), (string) ($args[1] ?? ''), (int) ($args[2] ?? 0)),
            'alarm_custom' => $this->askCustomMinutes($subscriber, (string) ($args[0] ?? ''), (int) ($args[1] ?? 0)),
            'alarm_custom_edit' => $this->askCustomMinutes($subscriber, null, (int) ($args[0] ?? 0)),
            'assignment' => $this->showAssignment($subscriber, (int) ($args[0] ?? 0)),
            'assignment_new' => $this->showAssignmentCoursePicker($subscriber),
            'assignment_course' => $this->startAssignmentTitle($subscriber, (int) ($args[0] ?? 0)),
            'assignment_done' => $this->toggleAssignment($subscriber, (int) ($args[0] ?? 0)),
            'assignment_del' => $this->deleteAssignment($subscriber, (int) ($args[0] ?? 0)),
            'back' => $this->runIntent($subscriber, $args[0] ?? 'help'),
            default => $this->send($subscriber->chat_id, 'این دکمه دیگر معتبر نیست. از منو استفاده کنید.', $this->mainKeyboard()),
        };
    }

    private function showSchedule(BaleSubscriber $subscriber): void
    {
        $user = $subscriber->user;
        $week = $this->schedule->week($user, CarbonImmutable::now());
        $occurrences = $this->schedule->occurrencesForWeek($user, $week['week_start']);
        $byDay = $occurrences->groupBy(fn (array $occurrence): int => $occurrence['session']->day_of_week);

        $lines = [
            '📅 <b>برنامه این هفته</b>',
            'هفته '.Number::format($week['week_number'], locale: 'fa').' — <b>هفته '.($week['is_odd'] ? 'فرد' : 'زوج').'</b>',
            JalaliDate::long($week['week_start']).' تا '.JalaliDate::long($week['week_end']),
            '',
        ];

        for ($day = 0; $day <= 6; $day++) {
            $lines[] = '<b>'.JalaliDate::weekdayName($day).'</b>';

            $dayOccurrences = $byDay->get($day, collect());

            if ($dayOccurrences->isEmpty()) {
                $lines[] = '<i>کلاسی نیست</i>';

                continue;
            }

            foreach ($dayOccurrences as $occurrence) {
                $lines[] = '• '.$occurrence['session']->timeRange().' — '.e($occurrence['session']->course->name);

                if (filled($occurrence['session']->course->location)) {
                    $lines[] = '  📍 '.e($occurrence['session']->course->location);
                }
            }

            $lines[] = '';
        }

        $this->send($subscriber->chat_id, rtrim(implode("\n", $lines)), $this->keyboard([
            [['text' => '📚 درس‌ها', 'callback_data' => 'menu:courses']],
            [['text' => '🔄 به‌روزرسانی', 'callback_data' => 'menu:schedule']],
        ]));
    }

    private function showCourses(BaleSubscriber $subscriber): void
    {
        $courses = Course::query()->ownedBy($subscriber->user)->orderBy('name')->get();

        if ($courses->isEmpty()) {
            $this->send($subscriber->chat_id, 'هنوز درسی اضافه نکرده‌اید. از سایت بخش «درس‌ها» اضافه کنید.', $this->mainKeyboard());

            return;
        }

        $buttons = $courses->map(fn (Course $course): array => [[
            'text' => '📚 '.$course->name,
            'callback_data' => 'course:'.$course->id,
        ]])->all();

        $this->send($subscriber->chat_id, '📚 <b>درس‌های شما</b>'."\n\n".'برای دیدن لینک‌ها و زمان کلاس‌ها یکی را انتخاب کنید:', $this->keyboard($buttons));
    }

    private function showCourse(BaleSubscriber $subscriber, int $courseId): void
    {
        $course = Course::query()
            ->with(['links', 'sessions.alarms'])
            ->ownedBy($subscriber->user)
            ->find($courseId);

        if ($course === null) {
            $this->send($subscriber->chat_id, 'درس پیدا نشد.');

            return;
        }

        $lines = ['📚 <b>'.e($course->name).'</b>'];

        if (filled($course->instructor)) {
            $lines[] = '👤 '.e($course->instructor);
        }

        if (filled($course->location)) {
            $lines[] = '📍 '.e($course->location);
        }

        $lines[] = '';
        $lines[] = '<b>زمان کلاس‌ها</b>';

        if ($course->sessions->isEmpty()) {
            $lines[] = '<i>زمانی ثبت نشده است</i>';
        }

        foreach ($course->sessions as $session) {
            $lines[] = '• '.$session->summary();

            if ($session->alarms->isNotEmpty()) {
                $lines[] = '  ⏰ '.$session->alarms->map(fn (Alarm $alarm): string => $alarm->label())->join('، ');
            }
        }

        $lines[] = '';
        $lines[] = '<b>لینک‌ها</b>';

        if ($course->links->isEmpty()) {
            $lines[] = '<i>لینکی ثبت نشده است</i>';
        }

        foreach ($course->links as $link) {
            $lines[] = '• <a href="'.e($link->url).'">'.e($link->title).'</a>';
            $lines[] = '  <code>'.e($link->url).'</code>';
        }

        $this->send($subscriber->chat_id, implode("\n", $lines), $this->keyboard([
            [['text' => '⏰ یادآورهای این درس', 'callback_data' => 'course_alarms:'.$course->id]],
            [['text' => '⬅️ درس‌ها', 'callback_data' => 'menu:courses']],
        ]));
    }

    private function showCourseAlarms(BaleSubscriber $subscriber, int $courseId): void
    {
        $course = Course::query()->with('sessions')->ownedBy($subscriber->user)->find($courseId);

        if ($course === null) {
            $this->send($subscriber->chat_id, 'درس پیدا نشد.');

            return;
        }

        if ($course->sessions->isEmpty()) {
            $this->send($subscriber->chat_id, 'برای این درس زمانی ثبت نشده است. ابتدا از سایت زمان کلاس را اضافه کنید.', $this->keyboard([
                [['text' => '⬅️ بازگشت', 'callback_data' => 'course:'.$course->id]],
            ]));

            return;
        }

        $buttons = $course->sessions->map(fn (ClassSession $session): array => [[
            'text' => '⏰ '.$session->summary(),
            'callback_data' => 'session:'.$session->id,
        ]])->all();

        $buttons[] = [['text' => '⬅️ بازگشت', 'callback_data' => 'course:'.$course->id]];

        $this->send($subscriber->chat_id, '⏰ <b>یادآورهای '.e($course->name).'</b>'."\n\n".'یک زمان کلاس را انتخاب کنید:', $this->keyboard($buttons));
    }

    private function showSession(BaleSubscriber $subscriber, int $sessionId): void
    {
        $session = ClassSession::query()
            ->with(['course', 'alarms'])
            ->ownedBy($subscriber->user)
            ->find($sessionId);

        if ($session === null) {
            $this->send($subscriber->chat_id, 'زمان کلاس پیدا نشد.');

            return;
        }

        $lines = [
            '⏰ <b>یادآورهای کلاس</b>',
            '',
            '📚 '.e($session->course->name),
            '📅 '.$session->summary(),
        ];

        if ($session->alarms->isEmpty()) {
            $lines[] = '';
            $lines[] = '<i>هنوز یادآوری تنظیم نشده است.</i>';
        } else {
            $lines[] = '';
            $lines[] = '<b>یادآورهای فعلی</b>';

            foreach ($session->alarms as $alarm) {
                $lines[] = '• '.$alarm->label();
            }
        }

        $buttons = $session->alarms->map(fn (Alarm $alarm): array => [
            ['text' => '✏️ '.$alarm->label(), 'callback_data' => 'alarm_edit:'.$alarm->id],
            ['text' => '❌ حذف', 'callback_data' => 'alarm_del:'.$alarm->id.':session:'.$session->id],
        ])->all();

        $buttons[] = [['text' => '➕ افزودن یادآور', 'callback_data' => 'alarm_add:session:'.$session->id]];
        $buttons[] = [['text' => '⬅️ بازگشت', 'callback_data' => 'course_alarms:'.$session->course_id]];

        $this->send($subscriber->chat_id, implode("\n", $lines), $this->keyboard($buttons));
    }

    private function showAssignments(BaleSubscriber $subscriber): void
    {
        $assignments = Assignment::query()
            ->with(['course', 'alarms'])
            ->ownedBy($subscriber->user)
            ->orderBy('is_done')
            ->orderBy('due_at')
            ->get();

        $lines = ['📝 <b>کارها و تحقیق‌ها</b>', ''];

        if ($assignments->isEmpty()) {
            $lines[] = '<i>کاری ثبت نشده است.</i>';
        }

        foreach ($assignments as $assignment) {
            $status = $assignment->is_done ? '✅' : ($assignment->isOverdue() ? '⚠️' : '🕒');
            $lines[] = $status.' <b>'.e($assignment->title).'</b>';

            if ($assignment->course !== null) {
                $lines[] = '  📚 '.e($assignment->course->name);
            }

            $lines[] = '  📅 '.$assignment->dueLabel();
            $lines[] = '';
        }

        $buttons = $assignments->map(fn (Assignment $assignment): array => [[
            'text' => ($assignment->is_done ? '✅ ' : '').Str::limit($assignment->title, 40),
            'callback_data' => 'assignment:'.$assignment->id,
        ]])->all();

        $buttons[] = [['text' => '➕ کار جدید', 'callback_data' => 'assignment_new']];
        $buttons[] = [['text' => '📅 برنامه هفته', 'callback_data' => 'menu:schedule']];

        $this->send($subscriber->chat_id, rtrim(implode("\n", $lines)), $this->keyboard($buttons));
    }

    private function showAssignment(BaleSubscriber $subscriber, int $assignmentId): void
    {
        $assignment = Assignment::query()
            ->with(['course', 'alarms'])
            ->ownedBy($subscriber->user)
            ->find($assignmentId);

        if ($assignment === null) {
            $this->send($subscriber->chat_id, 'کار پیدا نشد.');

            return;
        }

        $lines = [
            '📝 <b>'.e($assignment->title).'</b>',
            '',
            'وضعیت: '.($assignment->is_done ? '✅ انجام شده' : '🕒 در انتظار'),
            '📅 تحویل: '.$assignment->dueLabel(),
        ];

        if ($assignment->course !== null) {
            $lines[] = '📚 درس: '.e($assignment->course->name);
        }

        if (filled($assignment->description)) {
            $lines[] = '';
            $lines[] = e($assignment->description);
        }

        $lines[] = '';
        $lines[] = '<b>یادآورها</b>';

        if ($assignment->alarms->isEmpty()) {
            $lines[] = '<i>یادآوری تنظیم نشده است.</i>';
        }

        foreach ($assignment->alarms as $alarm) {
            $lines[] = '• '.$alarm->label();
        }

        $buttons = $assignment->alarms->map(fn (Alarm $alarm): array => [
            ['text' => '✏️ '.$alarm->label(), 'callback_data' => 'alarm_edit:'.$alarm->id],
            ['text' => '❌ حذف', 'callback_data' => 'alarm_del:'.$alarm->id.':assignment:'.$assignment->id],
        ])->all();

        $buttons[] = [['text' => '➕ افزودن یادآور', 'callback_data' => 'alarm_add:assignment:'.$assignment->id]];
        $buttons[] = [[
            'text' => $assignment->is_done ? '↩️ برگرداندن به انجام‌نشده' : '✅ انجام شد',
            'callback_data' => 'assignment_done:'.$assignment->id,
        ]];
        $buttons[] = [
            ['text' => '🗑 حذف کار', 'callback_data' => 'assignment_del:'.$assignment->id],
            ['text' => '⬅️ کارها', 'callback_data' => 'menu:assignments'],
        ];

        $this->send($subscriber->chat_id, implode("\n", $lines), $this->keyboard($buttons));
    }

    private function showAllAlarms(BaleSubscriber $subscriber): void
    {
        $alarms = Alarm::query()
            ->with('alarmable')
            ->ownedBy($subscriber->user)
            ->orderBy('minutes_before')
            ->get();

        $lines = ['⏰ <b>همه یادآورها</b>', ''];

        if ($alarms->isEmpty()) {
            $lines[] = '<i>یادآوری تنظیم نشده است.</i>';
        }

        foreach ($alarms as $alarm) {
            $target = $this->describeAlarmTarget($alarm);

            if ($target !== null) {
                $lines[] = '• '.$target;
                $lines[] = '  ⏱ '.$alarm->label();
            }
        }

        $this->send($subscriber->chat_id, rtrim(implode("\n", $lines))."\n\n".'برای ویرایش، از صفحه درس یا کار استفاده کنید.', $this->keyboard([
            [['text' => '📚 درس‌ها', 'callback_data' => 'menu:courses']],
            [['text' => '📝 کارها', 'callback_data' => 'menu:assignments']],
        ]));
    }

    private function showSettings(BaleSubscriber $subscriber): void
    {
        $user = $subscriber->user;
        $setting = $this->schedule->setting($user);
        $week = $this->schedule->week($user, CarbonImmutable::now(), $setting);

        $lines = [
            '⚙️ <b>تنظیمات</b>',
            '',
            '📅 شروع ترم: '.JalaliDate::long($setting->semester_start_date),
            '🔢 هفته جاری: <b>'.Number::format($week['week_number'], locale: 'fa').'</b> — <b>هفته '.($week['is_odd'] ? 'فرد' : 'زوج').'</b>',
            '',
            '<b>هفته‌های پیش رو</b>',
        ];

        for ($offset = 0; $offset <= 3; $offset++) {
            $future = $this->schedule->week($user, CarbonImmutable::now()->addWeeks($offset), $setting);
            $lines[] = '• هفته '.Number::format($future['week_number'], locale: 'fa').' ('.JalaliDate::format($future['week_start'], 'd MMMM').') — '.($future['is_odd'] ? 'فرد' : 'زوج');
        }

        $lines[] = '';
        $lines[] = 'برای تغییر شروع ترم یا وضعیت هفته، از سایت › تنظیمات استفاده کنید.';
        $lines[] = 'وضعیت اتصال: ✅ متصل';

        $this->send($subscriber->chat_id, implode("\n", $lines), $this->keyboard([
            [['text' => '📅 برنامه هفته', 'callback_data' => 'menu:schedule']],
        ]));
    }

    private function showAlarmPresets(BaleSubscriber $subscriber, string $type, int $id, string $mode): void
    {
        $target = $this->resolveAlarmable($subscriber->user, $type, $id);

        if ($target === null) {
            $this->send($subscriber->chat_id, 'موردی که می‌خواهید برایش یادآور بسازید پیدا نشد.');

            return;
        }

        $title = $type === 'session'
            ? 'کلاس '.$target->course->name.' — '.$target->summary()
            : 'کار «'.$target->title.'»';

        $buttons = [];
        $row = [];

        foreach (AlarmInterval::PRESETS as $minutes) {
            $row[] = ['text' => AlarmInterval::label($minutes), 'callback_data' => 'alarm_set:'.$type.':'.$id.':'.$minutes];

            if (count($row) === 2) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        $buttons[] = [['text' => '✍️ مقدار دلخواه (دقیقه)', 'callback_data' => 'alarm_custom:'.$type.':'.$id]];
        $buttons[] = [['text' => '⬅️ بازگشت', 'callback_data' => $type === 'session' ? 'session:'.$id : 'assignment:'.$id]];

        $this->send($subscriber->chat_id, '⏰ <b>چه زمانی قبل از '.e($title).' یادآوری شود؟</b>', $this->keyboard($buttons));
    }

    private function showEditPresets(BaleSubscriber $subscriber, int $alarmId): void
    {
        $alarm = Alarm::query()->ownedBy($subscriber->user)->find($alarmId);

        if ($alarm === null) {
            $this->send($subscriber->chat_id, 'یادآور پیدا نشد.');

            return;
        }

        $buttons = [];
        $row = [];

        foreach (AlarmInterval::PRESETS as $minutes) {
            $row[] = ['text' => AlarmInterval::label($minutes), 'callback_data' => 'alarm_upd:'.$alarm->id.':'.$minutes];

            if (count($row) === 2) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if ($row !== []) {
            $buttons[] = $row;
        }

        $buttons[] = [['text' => '✍️ مقدار دلخواه (دقیقه)', 'callback_data' => 'alarm_custom_edit:'.$alarm->id]];
        $buttons[] = [['text' => '⬅️ بازگشت', 'callback_data' => 'menu:alarms']];

        $this->send($subscriber->chat_id, '✏️ زمان یادآوری جدید را انتخاب کنید (فعلی: '.$alarm->label().'):', $this->keyboard($buttons));
    }

    private function createAlarm(BaleSubscriber $subscriber, string $type, int $id, int $minutes): void
    {
        $target = $this->resolveAlarmable($subscriber->user, $type, $id);

        if ($target === null || $minutes < 0 || $minutes > 20160) {
            $this->send($subscriber->chat_id, 'مقدار یادآور معتبر نیست.');

            return;
        }

        $alarm = Alarm::firstOrCreate(
            [
                'alarmable_type' => $target->getMorphClass(),
                'alarmable_id' => $target->getKey(),
                'minutes_before' => $minutes,
            ],
            ['user_id' => $subscriber->user_id, 'is_active' => true],
        );

        $this->send($subscriber->chat_id, $alarm->wasRecentlyCreated
            ? '✅ یادآور «'.$alarm->label().'» اضافه شد.'
            : 'این یادآور از قبل وجود داشت.');

        $this->showTargetAlarms($subscriber, $type, $id);
    }

    private function updateAlarm(BaleSubscriber $subscriber, int $alarmId, int $minutes): void
    {
        $alarm = Alarm::query()->ownedBy($subscriber->user)->find($alarmId);

        if ($alarm === null || $minutes < 0 || $minutes > 20160) {
            $this->send($subscriber->chat_id, 'یادآور یا مقدار جدید معتبر نیست.');

            return;
        }

        $duplicate = Alarm::query()
            ->where('alarmable_type', $alarm->alarmable_type)
            ->where('alarmable_id', $alarm->alarmable_id)
            ->where('minutes_before', $minutes)
            ->whereKeyNot($alarm->id)
            ->exists();

        if ($duplicate) {
            $this->send($subscriber->chat_id, 'این یادآور از قبل وجود دارد.');

            return;
        }

        $alarm->update(['minutes_before' => $minutes]);
        $this->send($subscriber->chat_id, '✅ یادآور به «'.$alarm->label().'» تغییر کرد.');

        $type = $alarm->alarmable instanceof ClassSession ? 'session' : 'assignment';
        $this->showTargetAlarms($subscriber, $type, (int) $alarm->alarmable_id);
    }

    private function deleteAlarm(BaleSubscriber $subscriber, int $alarmId, string $type, int $targetId): void
    {
        $alarm = Alarm::query()->ownedBy($subscriber->user)->find($alarmId);

        if ($alarm === null) {
            $this->send($subscriber->chat_id, 'یادآور پیدا نشد.');

            return;
        }

        $alarm->delete();
        $this->send($subscriber->chat_id, '🗑 یادآور حذف شد.');
        $this->showTargetAlarms($subscriber, $type, $targetId);
    }

    private function askCustomMinutes(BaleSubscriber $subscriber, ?string $type, int $id): void
    {
        if ($type === null) {
            $alarm = Alarm::query()->ownedBy($subscriber->user)->find($id);

            if ($alarm === null) {
                $this->send($subscriber->chat_id, 'یادآور پیدا نشد.');

                return;
            }

            $subscriber->startState('alarm.minutes', ['alarm_id' => $alarm->id]);

            $this->send($subscriber->chat_id, 'چند دقیقه قبل یادآوری شود؟ عددی بین ۰ تا ۲۰۱۶۰ بفرستید. (۰ = لحظه شروع)');

            return;
        }

        if ($this->resolveAlarmable($subscriber->user, $type, $id) === null) {
            $this->send($subscriber->chat_id, 'موردی پیدا نشد.');

            return;
        }

        $subscriber->startState('alarm.minutes', ['target_type' => $type, 'target_id' => $id]);
        $this->send($subscriber->chat_id, 'چند دقیقه قبل یادآوری شود؟ عددی بین ۰ تا ۲۰۱۶۰ بفرستید. (۰ = لحظه شروع)');
    }

    private function showTargetAlarms(BaleSubscriber $subscriber, string $type, int $id): void
    {
        if ($type === 'session') {
            $this->showSession($subscriber, $id);

            return;
        }

        $this->showAssignment($subscriber, $id);
    }

    private function showAssignmentCoursePicker(BaleSubscriber $subscriber): void
    {
        $courses = Course::query()->ownedBy($subscriber->user)->orderBy('name')->get();

        $buttons = $courses->map(fn (Course $course): array => [[
            'text' => '📚 '.$course->name,
            'callback_data' => 'assignment_course:'.$course->id,
        ]])->all();

        $buttons[] = [['text' => 'بدون درس', 'callback_data' => 'assignment_course:0']];
        $buttons[] = [['text' => '⬅️ بازگشت', 'callback_data' => 'menu:assignments']];

        $this->send($subscriber->chat_id, '📝 <b>کار جدید</b>'."\n\n".'این کار مربوط به کدام درس است؟', $this->keyboard($buttons));
    }

    private function startAssignmentTitle(BaleSubscriber $subscriber, int $courseId): void
    {
        $course = null;

        if ($courseId !== 0) {
            $course = Course::query()->ownedBy($subscriber->user)->find($courseId);

            if ($course === null) {
                $this->send($subscriber->chat_id, 'درس پیدا نشد.');

                return;
            }
        }

        $subscriber->startState('assignment.title', ['course_id' => $course?->id]);

        $this->send($subscriber->chat_id, '📝 عنوان کار یا تحقیق را بفرستید:');
    }

    private function toggleAssignment(BaleSubscriber $subscriber, int $assignmentId): void
    {
        $assignment = Assignment::query()->ownedBy($subscriber->user)->find($assignmentId);

        if ($assignment === null) {
            $this->send($subscriber->chat_id, 'کار پیدا نشد.');

            return;
        }

        $assignment->update(['is_done' => ! $assignment->is_done]);
        $this->showAssignment($subscriber, $assignment->id);
    }

    private function deleteAssignment(BaleSubscriber $subscriber, int $assignmentId): void
    {
        $assignment = Assignment::query()->ownedBy($subscriber->user)->find($assignmentId);

        if ($assignment === null) {
            $this->send($subscriber->chat_id, 'کار پیدا نشد.');

            return;
        }

        $assignment->delete();
        $this->send($subscriber->chat_id, '🗑 کار حذف شد.');
        $this->showAssignments($subscriber);
    }

    private function handleState(BaleSubscriber $subscriber, string $text): void
    {
        $context = $subscriber->context ?? [];

        match ($subscriber->state) {
            'assignment.title' => $this->stateAssignmentTitle($subscriber, $context, $text),
            'assignment.date' => $this->stateAssignmentDate($subscriber, $context, $text),
            'assignment.time' => $this->stateAssignmentTime($subscriber, $context, $text),
            'alarm.minutes' => $this->stateAlarmMinutes($subscriber, $context, $text),
            default => $subscriber->clearState(),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function stateAssignmentTitle(BaleSubscriber $subscriber, array $context, string $text): void
    {
        $title = trim($text);

        if ($title === '' || mb_strlen($title) > 255) {
            $this->send($subscriber->chat_id, 'عنوان باید بین ۱ تا ۲۵۵ نویسه باشد.');

            return;
        }

        $subscriber->startState('assignment.date', [...$context, 'title' => $title]);

        $this->send($subscriber->chat_id, '📅 تاریخ تحویل را به شمسی بفرستید. نمونه: <code>1405/08/23</code>');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function stateAssignmentDate(BaleSubscriber $subscriber, array $context, string $text): void
    {
        $date = JalaliDate::parse($text);

        if ($date === null) {
            $this->send($subscriber->chat_id, '❌ تاریخ معتبر نیست. دوباره به شکل <code>1405/08/23</code> بفرستید.');

            return;
        }

        $subscriber->startState('assignment.time', [...$context, 'date' => $date->toDateString()]);

        $this->send($subscriber->chat_id, '🕒 ساعت تحویل را بفرستید. نمونه: <code>14:30</code>'."\n".'اگر ساعت مهم نیست، بنویسید: <code>-</code> (۲۳:۵۹ ثبت می‌شود)');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function stateAssignmentTime(BaleSubscriber $subscriber, array $context, string $text): void
    {
        $time = in_array(trim($text), ['-', 'ندارد', 'بدون'], true) ? '23:59' : TimeInput::normalize($text);

        if ($time === null || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            $this->send($subscriber->chat_id, '❌ ساعت معتبر نیست. نمونه: <code>14:30</code> یا <code>-</code>');

            return;
        }

        $assignment = Assignment::create([
            'user_id' => $subscriber->user_id,
            'course_id' => $context['course_id'] ?? null,
            'title' => (string) ($context['title'] ?? 'کار بدون عنوان'),
            'due_at' => CarbonImmutable::parse(($context['date'] ?? now()->toDateString()).' '.$time),
            'is_done' => false,
        ]);

        $subscriber->clearState();

        $this->send($subscriber->chat_id, '✅ کار ثبت شد.');
        $this->showAssignment($subscriber, $assignment->id);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function stateAlarmMinutes(BaleSubscriber $subscriber, array $context, string $text): void
    {
        $minutes = (int) TimeInput::latinDigits(trim($text));

        if (trim($text) === '' || ! ctype_digit(TimeInput::latinDigits(trim($text))) || $minutes > 20160) {
            $this->send($subscriber->chat_id, '❌ عدد معتبر نیست. مقداری بین ۰ تا ۲۰۱۶۰ بفرستید.');

            return;
        }

        $subscriber->clearState();

        if (isset($context['alarm_id'])) {
            $this->updateAlarm($subscriber, (int) $context['alarm_id'], $minutes);

            return;
        }

        $this->createAlarm(
            $subscriber,
            (string) ($context['target_type'] ?? ''),
            (int) ($context['target_id'] ?? 0),
            $minutes,
        );
    }

    private function resolveAlarmable(User $user, string $type, int $id): ClassSession|Assignment|null
    {
        return match ($type) {
            'session' => ClassSession::query()->with('course')->ownedBy($user)->find($id),
            'assignment' => Assignment::query()->ownedBy($user)->find($id),
            default => null,
        };
    }

    private function describeAlarmTarget(Alarm $alarm): ?string
    {
        if ($alarm->alarmable instanceof ClassSession) {
            return '📚 '.$alarm->alarmable->course->name.' — '.$alarm->alarmable->summary();
        }

        if ($alarm->alarmable instanceof Assignment) {
            return '📝 '.$alarm->alarmable->title;
        }

        return null;
    }

    /**
     * @param  list<list<array{text: string, callback_data: string}>>  $rows
     * @return array<string, mixed>
     */
    private function keyboard(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    private function mainKeyboard(): array
    {
        return [
            'keyboard' => [
                [['text' => '📅 برنامه هفته'], ['text' => '📚 درس‌ها']],
                [['text' => '📝 کارها'], ['text' => '⏰ یادآورها']],
                [['text' => '⚙️ تنظیمات'], ['text' => '❓ راهنما']],
            ],
            'resize_keyboard' => true,
        ];
    }

    private function helpText(): string
    {
        return "از منوی زیر استفاده کنید:\n"
            ."📅 برنامه هفته — کلاس‌های هفته جاری\n"
            ."📚 درس‌ها — زمان‌ها و لینک‌های هر درس\n"
            ."📝 کارها — کارها و تحقیق‌ها و ساخت کار جدید\n"
            ."⏰ یادآورها — دیدن و ویرایش یادآورها\n"
            ."⚙️ تنظیمات — وضعیت هفته و ترم\n\n"
            ."دستورها: /schedule /courses /assignments /alarms /settings\n"
            .'برای لغو یک مرحله بنویسید: لغو';
    }

    private function send(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $this->bale->sendMessage($chatId, $text, $replyMarkup);
    }
}
