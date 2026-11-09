<?php

declare(strict_types=1);

namespace App\Domain\Extraction;

use App\Support\Regex;
use Normalizer;

/**
 * Turns pasted or extracted policy text into the canonical text that CekPDP
 * analyses and displays. Evidence offsets refer to this cleaned text.
 *
 * Cleaning never drops words. It normalises Unicode (NFC), line endings and
 * spacing, removes invisible control characters, and rejoins lines that were
 * hard-wrapped in the middle of a sentence (common when text is copied from a
 * PDF). Paragraph breaks, headings and list items are kept.
 */
final class TextCleaner
{
    /** Only lines at least this long are candidates for rejoining. */
    private const WRAP_MIN_LENGTH = 40;

    /** Bullets and numbering that start a list item. */
    private const LIST_MARKER = '/^(?:[-*•·▪‣◦]|\d{1,3}[.)]|[a-zA-Z][.)]|\([a-zA-Z0-9]{1,4}\))\s+/u';

    public function clean(#[\SensitiveParameter] string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_scrub($text, 'UTF-8');
        }

        $normalized = Normalizer::normalize($text, Normalizer::FORM_C);
        $text = is_string($normalized) ? $normalized : $text;

        $text = str_replace(["\r\n", "\r", "\u{2028}", "\u{2029}", "\u{0085}"], "\n", $text);

        // Byte-order marks, zero-width spaces and soft hyphens only get in the way of matching.
        $text = str_replace(["\u{FEFF}", "\u{200B}", "\u{2060}", "\u{00AD}"], '', $text);

        // Tabs and Unicode space characters become plain spaces.
        $text = Regex::replace('/[\t\x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $text);

        // Other control characters (anything but line breaks) are removed.
        $text = Regex::replace('/[^\P{Cc}\n]/u', '', $text);

        $lines = array_map(
            static fn (string $line): string => trim(Regex::replace('/ {2,}/', ' ', $line)),
            explode("\n", $text),
        );

        $text = implode("\n", $this->rejoinWrappedLines($lines));

        return trim(Regex::replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Joins a line to the previous one when the previous line stops mid-sentence
     * and the next one carries on in lower case. Lists, headings, short lines
     * (addresses, labels) and lines ending in punctuation are left alone.
     *
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function rejoinWrappedLines(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $last = array_key_last($result);

            if ($last !== null && $this->continuesPreviousLine($result[$last], $line)) {
                $previous = $result[$last];
                $result[$last] = str_ends_with($previous, '-') ? $previous.$line : $previous.' '.$line;

                continue;
            }

            $result[] = $line;
        }

        return $result;
    }

    private function continuesPreviousLine(string $previous, string $line): bool
    {
        if ($previous === '' || $line === '' || mb_strlen($previous) < self::WRAP_MIN_LENGTH) {
            return false;
        }

        if (str_starts_with($previous, '#') || str_starts_with($line, '#')) {
            return false;
        }

        if (Regex::matches('/[.!?:;]["”’)\]]*$/u', $previous)) {
            return false;
        }

        if (Regex::matches(self::LIST_MARKER, $line)) {
            return false;
        }

        return Regex::matches('/^\p{Ll}/u', $line);
    }
}
