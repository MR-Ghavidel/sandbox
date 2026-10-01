<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Repositories\NoteRepository;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function __construct(private NoteRepository $notes) {}

    /**
     * List the notes, pinned first, with an optional search.
     */
    public function index(Request $request): View
    {
        $search = $request->validate(['q' => ['nullable', 'string', 'max:255']])['q'] ?? null;

        return view('notes.index', [
            'notes' => $this->notes->search($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('notes.edit', ['note' => null]);
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        $noteId = $this->notes->create(
            title: $request->validated('title'),
            body: $request->validated('body'),
            isPinned: $request->boolean('is_pinned'),
        );

        return to_route('notes.show', $noteId)->with('status', 'یادداشت ذخیره شد.');
    }

    public function show(int $note): View
    {
        return view('notes.show', ['note' => $this->notes->findOrFail($note)]);
    }

    public function edit(int $note): View
    {
        return view('notes.edit', ['note' => $this->notes->findOrFail($note)]);
    }

    public function update(StoreNoteRequest $request, int $note): RedirectResponse
    {
        $this->notes->findOrFail($note);
        $this->notes->update($note, [
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        return to_route('notes.show', $note)->with('status', 'یادداشت ذخیره شد.');
    }

    /**
     * Pin or unpin a note from the list.
     */
    public function pin(Request $request, int $note): RedirectResponse
    {
        $this->notes->findOrFail($note);
        $this->notes->setPinned($note, $request->validate(['is_pinned' => ['required', 'boolean']])['is_pinned']);

        return back();
    }

    public function destroy(int $note): RedirectResponse
    {
        $this->notes->findOrFail($note);
        $this->notes->delete($note);

        return to_route('notes.index')->with('status', 'یادداشت حذف شد.');
    }

    /**
     * Render Markdown for the editor's live preview, with the same rules as a saved note.
     */
    public function preview(Request $request): JsonResponse
    {
        $body = $request->validate(['body' => ['nullable', 'string', 'max:100000']])['body'] ?? null;

        return response()->json(['html' => Markdown::render($body)]);
    }
}
