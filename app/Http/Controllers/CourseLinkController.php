<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseLinkRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseLinkController extends Controller
{
    public function store(StoreCourseLinkRequest $request, int $course): RedirectResponse
    {
        $course = $this->findCourse($request, $course);
        $course->links()->create($request->validated());

        return to_route('courses.show', $course)->with('status', 'لینک اضافه شد.');
    }

    public function update(StoreCourseLinkRequest $request, int $course, int $link): RedirectResponse
    {
        $course = $this->findCourse($request, $course);
        $course->links()->findOrFail($link)->update($request->validated());

        return to_route('courses.show', $course)->with('status', 'لینک ویرایش شد.');
    }

    public function destroy(Request $request, int $course, int $link): RedirectResponse
    {
        $course = $this->findCourse($request, $course);
        $course->links()->findOrFail($link)->delete();

        return to_route('courses.show', $course)->with('status', 'لینک حذف شد.');
    }

    private function findCourse(Request $request, int $id): Course
    {
        return Course::query()->ownedBy($request->user())->findOrFail($id);
    }
}
