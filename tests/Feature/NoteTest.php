<?php

namespace Tests\Feature;

use App\Repositories\NoteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-29 10:00:00');
    }

    public function test_a_note_is_created_and_shown_as_markdown_with_per_paragraph_direction(): void
    {
        $response = $this->post(route('notes.store'), [
            'title' => 'Docker نکته‌های',
            'body' => "Laravel را با Docker بالا آوردم.\n\n- مورد **مهم**",
            'is_pinned' => '0',
        ]);

        $noteId = DB::table('notes')->value('id');
        $response->assertRedirect(route('notes.show', $noteId));

        $this->get(route('notes.show', $noteId))
            ->assertOk()
            ->assertSee('<h1 dir="rtl"', false)
            ->assertSee('<p dir="rtl">Laravel را با Docker بالا آوردم.</p>', false)
            ->assertSee('<strong>مهم</strong>', false);
    }

    public function test_list_shows_pinned_notes_first_and_searches_title_and_body(): void
    {
        $notes = app(NoteRepository::class);
        $notes->create('Old pinned', 'body', isPinned: true);
        Carbon::setTestNow('2026-09-29 11:00:00');
        $notes->create('Newest', 'درباره **Redis**');

        $this->get(route('notes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Old pinned', 'Newest'])
            ->assertSee('درباره Redis');

        $this->get(route('notes.index', ['q' => 'Redis']))
            ->assertOk()
            ->assertSee('Newest')
            ->assertDontSee('Old pinned');
    }

    public function test_a_note_is_updated_pinned_and_deleted(): void
    {
        $noteId = app(NoteRepository::class)->create('Title', 'Body');

        $this->put(route('notes.update', $noteId), ['title' => 'عنوان تازه', 'body' => 'متن تازه', 'is_pinned' => '1'])
            ->assertRedirect(route('notes.show', $noteId));
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'title' => 'عنوان تازه', 'is_pinned' => true]);

        $this->patch(route('notes.pin', $noteId), ['is_pinned' => '0']);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'is_pinned' => false]);

        $this->delete(route('notes.destroy', $noteId))->assertRedirect(route('notes.index'));
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_title_is_required_and_the_editor_pages_render(): void
    {
        $this->post(route('notes.store'), ['title' => '', 'body' => 'x'])->assertSessionHasErrors('title');

        $noteId = app(NoteRepository::class)->create('Title', "# Heading\n\nمتن");

        $this->get(route('notes.create'))->assertOk()->assertSee('data-note-editor', false);
        $this->get(route('notes.edit', $noteId))->assertOk()->assertSee('<h1 dir="ltr">Heading</h1>', false);
        $this->get(route('notes.show', 999))->assertNotFound();
    }

    public function test_preview_renders_markdown_like_a_saved_note(): void
    {
        $this->postJson(route('notes.preview'), ['body' => 'سلام **Laravel**'])
            ->assertOk()
            ->assertExactJson(['html' => "<p dir=\"rtl\">سلام <strong>Laravel</strong></p>\n"]);
    }
}
