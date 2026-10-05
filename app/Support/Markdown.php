<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
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
 *
 * Uses the classic DOM extension (available on every PHP version this app supports) instead of the
 * PHP 8.4 "Dom\HTMLDocument" API, which does not exist on the 8.3 builds many shared hosts ship.
 */
class Markdown
{
    private const BLOCK_SELECTOR = '//p | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //ul | //ol | //blockquote | //table';

    private const NESTED_BLOCK_SELECTOR = 'ancestor::li | ancestor::th | ancestor::td';

    public static function render(?string $source): string
    {
        if (blank($source)) {
            return '';
        }

        $html = Str::markdown($source, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
        $body = self::parse($html);

        if ($body === null) {
            return $html;
        }

        $xpath = new DOMXPath($body->ownerDocument);

        foreach (self::query($xpath, self::BLOCK_SELECTOR, $body) as $block) {
            if (self::query($xpath, self::NESTED_BLOCK_SELECTOR, $block)->length > 0) {
                continue;
            }

            $block->setAttribute('dir', TextDirection::detect(self::textWithoutCode($block)));
        }

        foreach (self::query($xpath, '//pre | //code', $body) as $code) {
            $code->setAttribute('dir', TextDirection::LTR);
        }

        foreach (self::query($xpath, '//a[starts-with(@href, "http")]', $body) as $link) {
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener noreferrer');
        }

        return self::innerHtml($body);
    }

    /**
     * Plain text of the rendered note, shortened for cards and lists.
     */
    public static function excerpt(?string $source, int $limit = 180): string
    {
        return Str::limit(Str::squish(html_entity_decode(strip_tags(Str::markdown((string) $source, ['html_input' => 'strip'])))), $limit);
    }

    /**
     * Parse a fragment of HTML and return the element holding its nodes.
     *
     * The XML encoding hint keeps libxml from decoding the UTF-8 text (Persian letters included)
     * as ISO-8859-1, and the wrapper element keeps the fragment in a single place so a stray
     * text node cannot end up outside of it.
     */
    private static function parse(string $html): ?DOMElement
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $useInternalErrors = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8" ?><div data-markdown-root="1">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        $root = $document->documentElement;

        return $root instanceof DOMElement && $root->getAttribute('data-markdown-root') === '1' ? $root : null;
    }

    /**
     * Run an XPath query, always returning a (possibly empty) node list.
     */
    private static function query(DOMXPath $xpath, string $expression, DOMNode $context): \DOMNodeList
    {
        $nodes = $xpath->query($expression, $context);

        return $nodes instanceof \DOMNodeList ? $nodes : new \DOMNodeList;
    }

    /**
     * Serialize the children of a node, which is how the fragment has to be handed back to Blade.
     */
    private static function innerHtml(DOMNode $node): string
    {
        $document = $node->ownerDocument;
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }

    /**
     * Code (commands, paths, ...) is English even inside Persian text, so it does not vote on the direction.
     */
    private static function textWithoutCode(DOMNode $block): string
    {
        $text = '';

        foreach ($block->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($child->tagName === 'code') {
                    continue;
                }

                $text .= self::textWithoutCode($child);

                continue;
            }

            $text .= $child->nodeType === XML_TEXT_NODE ? $child->nodeValue : '';
        }

        return $text;
    }
}
