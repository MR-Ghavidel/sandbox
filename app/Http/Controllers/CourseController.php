<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use App\Support\JalaliDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::query()
            ->ownedBy($request->user())
            ->with('sessions')
            ->withCount(['links', 'assignments'])
            ->orderBy('name')
            ->get();

        return view('courses.index', ['courses' => $courses]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = Course::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'color' => $request->validated('color') ?? 'sky',
        ]);

        return to_route('courses.show', $course)->with('status', 'درس اضافه شد. اکنون زمان کلاس‌ها و لینک‌ها را وارد کنید.');
    }

    public function show(Request $request, int $course): View
    {
        $course = $this->findCourse($request, $course);

        return view('courses.show', [
            'course' => $course->load(['links', 'sessions.alarms', 'assignments']),
            'weekdays' => JalaliDate::weekdayNames(),
        ]);
    }

    public function edit(Request $request, int $course): View
    {
        return view('courses.edit', [
            'course' => $this->findCourse($request, $course),
        ]);
    }

    public function update(StoreCourseRequest $request, int $course): RedirectResponse
    {
        $course = $this->findCourse($request, $course);
        $course->update($request->validated());

        return to_route('courses.show', $course)->with('status', 'درس ویرایش شد.');
    }

    public function destroy(Request $request, int $course): RedirectResponse
    {
        $course = $this->findCourse($request, $course);

        // Delete sessions one by one so their polymorphic alarms are removed too.
        $course->sessions()->get()->each->delete();
        $course->delete();

        return to_route('courses.index')->with('status', 'درس و زمان‌های آن حذف شدند.');
    }

    private function findCourse(Request $request, int $id): Course
    {
        return Course::query()->ownedBy($request->user())->findOrFail($id);
    }
}
