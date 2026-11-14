<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlarmRequest;
use App\Models\Alarm;
use App\Models\Assignment;
use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AlarmController extends Controller
{
    public function store(StoreAlarmRequest $request): RedirectResponse
    {
        $alarmable = $this->resolveTarget($request, (string) $request->validated('target'));

        $alarm = Alarm::firstOrCreate(
            [
                'alarmable_type' => $alarmable->getMorphClass(),
                'alarmable_id' => $alarmable->getKey(),
                'minutes_before' => (int) $request->validated('minutes_before'),
            ],
            ['user_id' => $request->user()->id, 'is_active' => true],
        );

        return back()->with('status', $alarm->wasRecentlyCreated
            ? 'یادآور «'.$alarm->label().'» اضافه شد.'
            : 'این یادآور از قبل وجود داشت.');
    }

    public function update(Request $request, int $alarm): RedirectResponse
    {
        $savedAlarm = Alarm::query()->ownedBy($request->user())->findOrFail($alarm);

        $validated = $request->validate([
            'minutes_before' => ['required', 'integer', 'between:0,20160'],
        ]);

        $duplicate = Alarm::query()
            ->where('alarmable_type', $savedAlarm->alarmable_type)
            ->where('alarmable_id', $savedAlarm->alarmable_id)
            ->where('minutes_before', $validated['minutes_before'])
            ->whereKeyNot($savedAlarm->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['minutes_before' => 'این یادآور از قبل وجود دارد.']);
        }

        $savedAlarm->update(['minutes_before' => $validated['minutes_before']]);

        return back()->with('status', 'یادآور ویرایش شد.');
    }

    public function destroy(Request $request, int $alarm): RedirectResponse
    {
        Alarm::query()->ownedBy($request->user())->findOrFail($alarm)->delete();

        return back()->with('status', 'یادآور حذف شد.');
    }

    private function resolveTarget(Request $request, string $target): ClassSession|Assignment
    {
        [$type, $id] = explode(':', $target);

        $alarmable = match ($type) {
            'session' => ClassSession::query()->ownedBy($request->user())->find((int) $id),
            'assignment' => Assignment::query()->ownedBy($request->user())->find((int) $id),
            default => null,
        };

        abort_if($alarmable === null, 404);

        return $alarmable;
    }
}
