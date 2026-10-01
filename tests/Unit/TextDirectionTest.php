<?php

namespace Tests\Unit;

use App\Support\TextDirection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TextDirectionTest extends TestCase
{
    /**
     * @return array<string, array{?string, string}>
     */
    public static function texts(): array
    {
        return [
            'persian' => ['امروز جلسه داشتیم', 'rtl'],
            'persian with one english word' => ['امروز جلسه deploy داشتیم', 'rtl'],
            'persian starting with an english word' => ['Laravel فریمورک خیلی خوبی است', 'rtl'],
            'english' => ['Meeting notes for today', 'ltr'],
            'english with one persian word' => ['Talked to علی about the release plan', 'ltr'],
            'tie goes right-to-left' => ['Laravel فریمورک', 'rtl'],
            'digits and symbols only use the default' => ['12:30 - 14:00', 'rtl'],
            'empty uses the default' => [null, 'rtl'],
        ];
    }

    #[DataProvider('texts')]
    public function test_the_script_with_more_words_decides_the_direction(?string $text, string $expected): void
    {
        $this->assertSame($expected, TextDirection::detect($text));
    }

    public function test_the_default_can_be_changed(): void
    {
        $this->assertSame('ltr', TextDirection::detect('123', TextDirection::LTR));
    }
}
