<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = Assignment::query()
            ->with(['course', 'alarms'])
            ->ownedBy($request->user())
            ->orderBy('due_at')
            ->get();

        return view('assignments.index', [
            'assignments' => $assignments->where('is_done', false)->values(),
            'doneAssignments' => $assignments->where('is_done', true)->sortByDesc('due_at')->values(),
            'courses' => Course::query()->ownedBy($request->user())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAssignmentRequest $request): RedirectResponse
    {
        Assignment::create($this->attributes($request));

        return to_route('assignments.index')->with('status', 'کار اضافه شد.');
    }

    public function update(StoreAssignmentRequest $request, int $assignment): RedirectResponse
    {
        $savedAssignment = $this->findAssignment($request, $assignment);
        $savedAssignment->update($this->attributes($request));

        return to_route('assignments.index')->with('status', 'کار ویرایش شد.');
    }

    public function toggleDone(Request $request, int $assignment): RedirectResponse
    {
        $savedAssignment = $this->findAssignment($request, $assignment);
        $savedAssignment->update(['is_done' => ! $savedAssignment->is_done]);

        return back()->with('status', $savedAssignment->is_done ? 'کار انجام‌شده شد.' : 'کار به فهرست کارها برگشت.');
    }

    public function destroy(Request $request, int $assignment): RedirectResponse
    {
        $this->findAssignment($request, $assignment)->delete();

        return to_route('assignments.index')->with('status', 'کار حذف شد.');
    }

    /**
     * @return array{title: string, course_id: ?int, description: ?string, due_at: CarbonImmutable, user_id: int}
     */
    private function attributes(StoreAssignmentRequest $request): array
    {
        return [
            'user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'course_id' => $request->validated('course_id'),
            'description' => $request->validated('description'),
            'due_at' => $request->dueAt(),
        ];
    }

    private function findAssignment(Request $request, int $id): Assignment
    {
        return Assignment::query()->ownedBy($request->user())->findOrFail($id);
    }
}
