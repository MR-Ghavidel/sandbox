<?php

namespace App\Support;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Str;

/**
 * Renders notes written in Markdown (GitHub flavored) to safe HTML for mixed Persian/English text.
 *
 * - Raw HTML in the source is escaped and unsafe links (javascript:, ...) are dropped.
 * - Every block (paragraph, heading, list, quote, table) gets its own "dir", chosen by TextDirection
 *   from its words, so an English word, even as the first word, does not flip a Persian paragraph.
 *   List items and table cells follow their list or table, so one English item stays in line.
 *   Code is always left-to-right.
 * - External links open in a new tab.
 */
class Markdown
{
    private const BLOCK_SELECTOR = 'p, h1, h2, h3, h4, h5, h6, ul, ol, blockquote, table';

    public static function render(?string $source): string
    {
        if (blank($source)) {
            return '';
        }

        $html = Str::markdown($source, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
        $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR);

        foreach ($document->body->querySelectorAll(self::BLOCK_SELECTOR) as $block) {
            if ($block->parentElement?->closest('li, th, td') !== null) {
                continue;
            }

            $block->setAttribute('dir', TextDirection::detect(self::textWithoutCode($block)));
        }

        foreach ($document->body->querySelectorAll('pre, code') as $code) {
            $code->setAttribute('dir', TextDirection::LTR);
        }

        foreach ($document->body->querySelectorAll('a[href^="http"]') as $link) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener noreferrer');
        }

        return $document->body->innerHTML;
    }

    /**
     * Plain text of the rendered note, shortened for cards and lists.
     */
    public static function excerpt(?string $source, int $limit = 180): string
    {
        return Str::limit(Str::squish(html_entity_decode(strip_tags(Str::markdown((string) $source, ['html_input' => 'strip'])))), $limit);
    }

    /**
     * Code (commands, paths, ...) is English even inside Persian text, so it does not vote on the direction.
     */
    private static function textWithoutCode(Element $block): string
    {
        $copy = $block->cloneNode(true);

        foreach ($copy->querySelectorAll('code') as $code) {
            $code->remove();
        }

        return $copy->textContent;
    }
}
