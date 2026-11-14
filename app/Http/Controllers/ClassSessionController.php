<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassSessionRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    public function store(StoreClassSessionRequest $request, int $course): RedirectResponse
    {
        $course = $this->findCourse($request, $course);

        $course->sessions()->create([
            ...$request->validated(),
            'meets_odd_weeks' => $request->boolean('meets_odd_weeks'),
            'meets_even_weeks' => $request->boolean('meets_even_weeks'),
        ]);

        return to_route('courses.show', $course)->with('status', 'زمان کلاس اضافه شد.');
    }

    public function update(StoreClassSessionRequest $request, int $course, int $session): RedirectResponse
    {
        $course = $this->findCourse($request, $course);

        $course->sessions()->findOrFail($session)->update([
            ...$request->validated(),
            'meets_odd_weeks' => $request->boolean('meets_odd_weeks'),
            'meets_even_weeks' => $request->boolean('meets_even_weeks'),
        ]);

        return to_route('courses.show', $course)->with('status', 'زمان کلاس ویرایش شد.');
    }

    public function destroy(Request $request, int $course, int $session): RedirectResponse
    {
        $course = $this->findCourse($request, $course);
        $course->sessions()->findOrFail($session)->delete();

        return to_route('courses.show', $course)->with('status', 'زمان کلاس حذف شد.');
    }

    private function findCourse(Request $request, int $id): Course
    {
        return Course::query()->ownedBy($request->user())->findOrFail($id);
    }
}
