<?php

namespace App\Support;

use App\Models\PostBlock;
use Illuminate\Support\HtmlString;

/**
 * A small, safe formatting language for post text — no raw HTML is ever accepted.
 *
 *   **bold**   *italic*   ==highlight==   `code`   [label](https://link)
 *   - bullet lists / 1. numbered lists (one item per line)
 *   Blank line = new paragraph, single line break = line break.
 *
 * Everything is HTML-escaped first; only the markup above becomes tags.
 */
class RichText
{
    public static function render(?string $text): HtmlString
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
        if ($text === '') {
            return new HtmlString('');
        }

        $html = [];
        foreach (preg_split('/\n{2,}/', $text) as $chunk) {
            $lines = explode("\n", $chunk);
            if (self::all($lines, '/^\s*[-*•]\s+/')) {
                $html[] = '<ul>'.implode('', array_map(fn ($l) => '<li>'.self::inline(preg_replace('/^\s*[-*•]\s+/', '', $l)).'</li>', $lines)).'</ul>';
            } elseif (self::all($lines, '/^\s*\d+[.)]\s+/')) {
                $html[] = '<ol>'.implode('', array_map(fn ($l) => '<li>'.self::inline(preg_replace('/^\s*\d+[.)]\s+/', '', $l)).'</li>', $lines)).'</ol>';
            } else {
                $html[] = '<p>'.implode('<br>', array_map(fn ($l) => self::inline($l), $lines)).'</p>';
            }
        }

        return new HtmlString(implode("\n", $html));
    }

    /** Inline marks only (for list items, captions, FAQ answers). */
    public static function inline(?string $text): string
    {
        $s = e((string) $text);
        // `code` first so its contents aren't formatted
        $codes = [];
        $s = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codes) {
            $codes[] = '<code>'.$m[1].'</code>';

            return "\u{0}".(count($codes) - 1)."\u{0}";
        }, $s);
        // [label](url) — only safe schemes
        $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $url = PostBlock::safeUrl(html_entity_decode($m[2]));
            if (! $url) {
                return $m[1];
            }
            $external = preg_match('~^https?://~i', $url) && ! str_starts_with($url, rtrim((string) config('app.url'), '/'));

            return '<a href="'.e($url).'"'.($external ? ' target="_blank" rel="noopener nofollow"' : '').'>'.$m[1].'</a>';
        }, $s);
        $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
        $s = preg_replace('/==(.+?)==/s', '<mark>$1</mark>', $s);
        $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/s', '<em>$1</em>', $s);
        $s = preg_replace('/(?<![_\w])_(?!\s)(.+?)(?<!\s)_(?![_\w])/s', '<em>$1</em>', $s);

        return preg_replace_callback("/\u{0}(\d+)\u{0}/", fn ($m) => $codes[(int) $m[1]] ?? '', $s);
    }

    /** Plain text with the markers removed (for excerpts and reading time). */
    public static function plain(?string $text): string
    {
        $s = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', (string) $text);

        return trim(preg_replace('/(\*\*|==|`|(?<!\w)[*_](?!\s))/', '', $s));
    }

    private static function all(array $lines, string $re): bool
    {
        foreach ($lines as $l) {
            if (! preg_match($re, $l)) {
                return false;
            }
        }

        return count($lines) > 0;
    }
}
