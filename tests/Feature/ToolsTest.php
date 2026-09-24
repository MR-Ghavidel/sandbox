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
