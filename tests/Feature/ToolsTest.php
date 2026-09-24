<?php

namespace Tests\Feature;

use Tests\TestCase;

class ToolsTest extends TestCase
{
    public function test_tools_page_lists_every_tool_and_is_in_the_drawer_menu(): void
    {
        $response = $this->get(route('tools.index'))->assertOk();

        foreach (config('tools.items') as $tool) {
            $response->assertSee($tool['label'])->assertSee('href="'.route($tool['route']).'"', false);
        }

        $response->assertSee('href="'.route('tools.index').'"', false)->assertSee('aria-current="page"', false);
    }

    public function test_drawer_lists_tools_as_a_collapsible_submenu_opened_on_tool_pages(): void
    {
        // On the tools overview the submenu is highlighted but not forced open, since no sub-item is active.
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('data-collapsible="nav-tools.index"', false)
            ->assertSee('نمایش زیرمجموعه‌های ابزارها')
            ->assertSee('href="'.route('tools.json').'"', false)
            ->assertSee('href="'.route('tools.timestamp').'"', false)
            ->assertDontSee('data-collapse-force-open', false);

        $this->get(route('tools.json'))
            ->assertOk()
            ->assertSee('data-collapse-force-open', false)
            ->assertSeeInOrder(['href="'.route('tools.json').'" class="block rounded-md px-3 py-1.5 text-sm bg-sky-50 dark:bg-sky-950/40 font-medium text-sky-700 dark:text-sky-300"', 'aria-current="page"', 'مرتب‌سازی JSON'], false);
    }

    public function test_layout_has_the_theme_toggle_and_applies_the_theme_before_paint(): void
    {
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee("localStorage.getItem('theme')", false)
            ->assertSee('prefers-color-scheme: dark', false);
    }

    public function test_layout_marks_the_regions_replaced_by_soft_navigation(): void
    {
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee('data-page dir="rtl"', false)
            ->assertSee('data-scroll-container dir="ltr"', false)
            ->assertSee('data-page-title', false)
            ->assertSee('data-drawer-nav', false)
            ->assertSee('data-navigation-progress', false);
    }

    public function test_json_formatter_page_renders_its_input_output_and_copy_button(): void
    {
        $this->get(route('tools.json'))
            ->assertOk()
            ->assertSee('data-json-formatter', false)
            ->assertSee('data-json-input', false)
            ->assertSee('data-copy-from="[data-json-output]"', false);
    }

    public function test_timestamp_page_renders_both_directions(): void
    {
        $this->get(route('tools.timestamp'))
            ->assertOk()
            ->assertSee('data-timestamp-tool', false)
            ->assertSee('تایم‌استمپ ← تاریخ')
            ->assertSee('تاریخ ← تایم‌استمپ')
            ->assertSee('value="Asia/Tehran"', false);
    }
}
