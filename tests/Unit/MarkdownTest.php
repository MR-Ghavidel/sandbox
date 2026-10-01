<?php

namespace Tests\Unit;

use App\Support\Markdown;
use Tests\TestCase;

class MarkdownTest extends TestCase
{
    public function test_each_block_gets_the_direction_of_its_own_words(): void
    {
        $html = Markdown::render("Laravel یک فریمورک خوب برای PHP است.\n\nThis paragraph is English with یک word.\n\n## تیتر با Docker");

        $this->assertStringContainsString('<p dir="rtl">Laravel یک فریمورک', $html);
        $this->assertStringContainsString('<p dir="ltr">This paragraph', $html);
        $this->assertStringContainsString('<h2 dir="rtl">تیتر با Docker</h2>', $html);
    }

    public function test_code_is_left_to_right_and_does_not_vote_on_the_direction(): void
    {
        $html = Markdown::render("اجرا کنید: `php artisan migrate --force --seed`\n\n```bash\nnpm run build\n```");

        $this->assertStringContainsString('<p dir="rtl">اجرا کنید: <code dir="ltr">php artisan migrate --force --seed</code></p>', $html);
        $this->assertStringContainsString('<pre dir="ltr">', $html);
    }

    public function test_list_items_and_table_cells_follow_their_list_or_table(): void
    {
        $html = Markdown::render("- مورد اول\n- item\n- مورد سوم\n\n| نام | Name |\n|---|---|\n| علی | Ali |");

        $this->assertStringContainsString('<ul dir="rtl">', $html);
        $this->assertStringContainsString('<li>item</li>', $html);
        $this->assertStringContainsString('<table dir="rtl">', $html);
        $this->assertStringContainsString('<td>Ali</td>', $html);
    }

    public function test_raw_html_and_unsafe_links_are_neutralized_and_external_links_open_in_a_new_tab(): void
    {
        $html = Markdown::render("<script>alert(1)</script>\n\n[bad](javascript:alert(1)) [good](https://laravel.com)");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<a href="https://laravel.com" target="_blank" rel="noopener noreferrer">good</a>', $html);
    }

    public function test_excerpt_is_plain_text(): void
    {
        $this->assertSame('تیتر متن پررنگ و code', Markdown::excerpt("# تیتر\n\nمتن **پررنگ** و `code`"));
        $this->assertSame('', Markdown::render(null));
    }
}
