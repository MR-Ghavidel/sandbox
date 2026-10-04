<?php

namespace Tests\Feature;

use App\Repositories\SiteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_site_is_added_with_its_host_as_the_default_title_and_duplicates_are_rejected(): void
    {
        $this->post(route('sites.store'), ['url' => 'https://www.GitHub.com/dashboard', 'title' => ''])
            ->assertRedirect(route('sites.index'));

        $this->assertDatabaseHas('sites', ['title' => 'github.com', 'host' => 'github.com', 'url' => 'https://www.GitHub.com/dashboard']);

        $this->post(route('sites.store'), ['url' => 'https://github.com/'])->assertSessionHasErrors('url');
        $this->post(route('sites.store'), ['url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->assertDatabaseCount('sites', 1);
    }

    public function test_list_shows_pinned_then_most_opened_sites_and_opening_counts_the_visit(): void
    {
        $sites = app(SiteRepository::class);
        $sites->create(['title' => 'Rarely used', 'url' => 'https://rare.test', 'host' => 'rare.test']);
        $often = $sites->create(['title' => 'Often used', 'url' => 'https://often.test/app', 'host' => 'often.test']);
        $sites->create(['title' => 'Pinned', 'url' => 'https://pinned.test', 'host' => 'pinned.test', 'is_pinned' => true]);

        $this->get(route('sites.open', $often))->assertRedirect('https://often.test/app');

        $this->assertDatabaseHas('sites', ['id' => $often, 'open_count' => 1]);
        $this->get(route('sites.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pinned', 'Often used', 'Rarely used']);
    }

    public function test_a_site_is_edited_pinned_and_deleted(): void
    {
        $siteId = app(SiteRepository::class)->create(['title' => 'Old', 'url' => 'https://old.test', 'host' => 'old.test']);

        $this->get(route('sites.edit', $siteId))->assertOk()->assertSee('https://old.test');

        // Saving a site with its own host is not a duplicate.
        $this->put(route('sites.update', $siteId), ['url' => 'https://old.test/new', 'title' => 'مستندات', 'description' => 'Docs'])
            ->assertRedirect(route('sites.index'));
        $this->assertDatabaseHas('sites', ['id' => $siteId, 'title' => 'مستندات', 'url' => 'https://old.test/new', 'description' => 'Docs']);

        $this->patch(route('sites.pin', $siteId), ['is_pinned' => '1']);
        $this->assertDatabaseHas('sites', ['id' => $siteId, 'is_pinned' => true]);

        $this->delete(route('sites.destroy', $siteId))->assertRedirect(route('sites.index'));
        $this->assertDatabaseCount('sites', 0);
    }

    public function test_extension_sends_browser_sites_without_csrf_and_they_are_merged_by_host(): void
    {
        $response = $this->postJson(route('site-imports.store'), ['sites' => [
            ['url' => 'https://github.com/', 'title' => 'GitHub', 'visit_count' => null, 'sources' => ['top_sites']],
            ['url' => 'https://www.github.com/', 'title' => 'GitHub Home', 'visit_count' => 40, 'sources' => ['history']],
            ['url' => 'https://laravel.com/docs', 'title' => 'Laravel Docs', 'visit_count' => null, 'sources' => ['bookmarks']],
            ['url' => 'https://news.test/', 'title' => '', 'visit_count' => 5, 'sources' => ['history']],
            ['url' => 'file:///C:/notes.txt', 'title' => 'Local file', 'visit_count' => null, 'sources' => ['bookmarks']],
        ]])->assertCreated();

        $import = DB::table('site_imports')->sole();
        $sites = collect(json_decode($import->sites, true))->keyBy('host');

        $this->assertSame(route('site-imports.show', $import->id), $response->json('preview_url'));
        $this->assertSame(['github.com', 'laravel.com', 'news.test'], $sites->keys()->all());
        $this->assertSame(['url' => 'https://github.com/', 'host' => 'github.com', 'title' => 'GitHub', 'visit_count' => 40, 'sources' => ['top_sites', 'history']], $sites['github.com']);
        $this->assertSame('news.test', $sites['news.test']['title']);
    }

    public function test_chosen_browser_sites_are_saved_and_already_saved_ones_are_skipped(): void
    {
        app(SiteRepository::class)->create(['title' => 'My GitHub', 'url' => 'https://github.com', 'host' => 'github.com']);

        $previewUrl = $this->postJson(route('site-imports.store'), ['sites' => [
            ['url' => 'https://github.com/', 'title' => 'GitHub', 'visit_count' => 40, 'sources' => ['history']],
            ['url' => 'https://laravel.com/docs', 'title' => 'Laravel Docs', 'visit_count' => null, 'sources' => ['bookmarks']],
            ['url' => 'https://news.test/', 'title' => 'News', 'visit_count' => 5, 'sources' => ['history']],
        ]])->json('preview_url');

        $this->get($previewUrl)
            ->assertOk()
            ->assertSee('قبلاً ذخیره شده')
            ->assertSee('Laravel Docs');

        $importId = DB::table('site_imports')->value('id');

        $this->post(route('site-imports.apply', $importId), ['hosts' => ['github.com', 'laravel.com']])
            ->assertRedirect(route('sites.index'));

        $this->assertDatabaseCount('sites', 2);
        $this->assertDatabaseHas('sites', ['host' => 'laravel.com', 'title' => 'Laravel Docs', 'url' => 'https://laravel.com/docs']);
        $this->assertDatabaseHas('sites', ['host' => 'github.com', 'title' => 'My GitHub']);
        $this->assertNotNull(DB::table('site_imports')->value('applied_at'));

        $this->post(route('site-imports.apply', $importId), ['hosts' => ['news.test']]);
        $this->assertDatabaseMissing('sites', ['host' => 'news.test']);
    }

    public function test_the_logo_linked_from_the_page_is_downloaded_once_and_served_from_disk(): void
    {
        Storage::fake('local');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        Http::preventStrayRequests()->fake([
            'https://docs.test/guide/intro' => Http::response('<html><head><link rel="icon" href="/small.ico" sizes="16x16"><link rel="apple-touch-icon" href="../img/logo.png"></head></html>'),
            'https://docs.test/img/logo.png' => Http::response($png, headers: ['Content-Type' => 'application/octet-stream']),
        ]);
        $siteId = app(SiteRepository::class)->create(['title' => 'Docs', 'url' => 'https://docs.test/guide/intro', 'host' => 'docs.test']);

        $this->get(route('sites.icon', $siteId))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('sites.icon', $siteId))->assertOk();

        Http::assertSentCount(2);
        Storage::disk('local')->assertExists("site-icons/{$siteId}.png");
        $this->assertNotNull(DB::table('sites')->where('id', $siteId)->value('icon_checked_at'));
    }

    public function test_a_site_without_a_logo_is_not_looked_at_again_until_its_host_changes(): void
    {
        Storage::fake('local');
        Http::preventStrayRequests()->fake([
            'https://plain.test/favicon.ico' => Http::response('Not found', 404),
            'https://plain.test*' => Http::response('<html><head><title>Plain</title></head></html>'),
        ]);
        $siteId = app(SiteRepository::class)->create(['title' => 'Plain', 'url' => 'https://plain.test', 'host' => 'plain.test']);

        $this->get(route('sites.icon', $siteId))->assertNotFound();
        $this->get(route('sites.icon', $siteId))->assertNotFound();

        Http::assertSentCount(2);
        $this->assertDatabaseHas('sites', ['id' => $siteId, 'icon_path' => null]);
        $this->assertNotNull(DB::table('sites')->where('id', $siteId)->value('icon_checked_at'));

        $this->put(route('sites.update', $siteId), ['url' => 'https://other.test', 'title' => 'Other']);
        $this->assertDatabaseHas('sites', ['id' => $siteId, 'icon_checked_at' => null]);
    }
}
