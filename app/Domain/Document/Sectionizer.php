<?php

declare(strict_types=1);

namespace App\Domain\Document;

use App\Support\Regex;
use SensitiveParameter;

/**
 * Splits cleaned policy text into sections with stable IDs (S01, S02, … in
 * document order).
 *
 * - When the policy has headings (Markdown "#" headings, or short title-like
 *   lines that start a block), each section is a heading plus the paragraphs
 *   under it. Text before the first heading is its own section.
 * - Without headings, each paragraph is a section.
 * - Sections longer than MAX_SECTION_BYTES continue in a new section with the
 *   same heading, split between paragraphs (or between lines for one very long
 *   paragraph).
 *
 * Every non-blank character of the text belongs to exactly one section.
 */
final class Sectionizer
{
    public const MAX_SECTION_BYTES = 8000;

    private const HEADING_MAX_CHARACTERS = 100;

    private const HEADING_MAX_WORDS = 12;

    /**
     * @return list<Section>
     */
    public function split(#[SensitiveParameter] string $text): array
    {
        $segments = $this->segments($text);
        $hasHeadings = array_any($segments, static fn (array $segment): bool => $segment['heading'] !== null);

        $groups = $hasHeadings
            ? $this->groupUnderHeadings($segments)
            : array_map(static fn (array $segment): array => [$segment], $segments);

        $sections = [];

        foreach ($groups as $group) {
            $heading = $group[0]['heading'];

            foreach ($this->bounded($group) as $index => $part) {
                $start = $part[0]['start'];
                $end = $part[array_key_last($part)]['end'];

                $sections[] = new Section(
                    id: sprintf('S%02d', count($sections) + 1),
                    heading: $heading,
                    start: $start,
                    end: $end,
                    text: substr($text, $start, $end - $start),
                    continued: $index > 0,
                );
            }
        }

        return $sections;
    }

    /**
     * Headings and paragraphs, with byte offsets. A paragraph is a run of
     * non-blank lines; a heading line always stands alone.
     *
     * @return list<array{heading: string|null, start: int, end: int, lines: list<array{int, int}>}>
     */
    private function segments(string $text): array
    {
        $lines = $this->lines($text);
        $segments = [];
        $paragraph = null;

        foreach ($lines as $index => [$line, $start, $end]) {
            if ($line === '') {
                if ($paragraph !== null) {
                    $segments[] = $paragraph;
                    $paragraph = null;
                }

                continue;
            }

            $heading = $this->headingText($lines, $index);

            if ($heading !== null) {
                if ($paragraph !== null) {
                    $segments[] = $paragraph;
                    $paragraph = null;
                }

                $segments[] = ['heading' => $heading, 'start' => $start, 'end' => $end, 'lines' => [[$start, $end]]];

                continue;
            }

            if ($paragraph === null) {
                $paragraph = ['heading' => null, 'start' => $start, 'end' => $end, 'lines' => [[$start, $end]]];
            } else {
                $paragraph['end'] = $end;
                $paragraph['lines'][] = [$start, $end];
            }
        }

        if ($paragraph !== null) {
            $segments[] = $paragraph;
        }

        return $segments;
    }

    /**
     * @return list<array{string, int, int}> line text, start offset, end offset
     */
    private function lines(string $text): array
    {
        $lines = [];
        $offset = 0;

        foreach (explode("\n", $text) as $line) {
            $leading = strlen($line) - strlen(ltrim($line));
            $lines[] = [trim($line), $offset + $leading, $offset + strlen(rtrim($line))];
            $offset += strlen($line) + 1;
        }

        return $lines;
    }

    /**
     * The heading text if the line is a heading, otherwise null.
     *
     * @param  list<array{string, int, int}>  $lines
     */
    private function headingText(array $lines, int $index): ?string
    {
        $line = $lines[$index][0];

        $markdown = Regex::first('/^#{1,6}\s+\S.*$/u', $line);

        if ($markdown !== null) {
            return trim(Regex::replace('/\s#+$/u', '', ltrim($line, '# ')));
        }

        return $this->looksLikeHeading($lines, $index) ? $line : null;
    }

    /**
     * A short, title-like line at the start of a block that is followed by more
     * text, such as "Data yang Kami Kumpulkan" or "4. Your Rights".
     *
     * @param  list<array{string, int, int}>  $lines
     */
    private function looksLikeHeading(array $lines, int $index): bool
    {
        $line = $lines[$index][0];
        $previous = $index > 0 ? $lines[$index - 1][0] : '';

        if ($previous !== '' && ! Regex::matches('/[.!?]["”’)\]]*$/u', $previous)) {
            return false;
        }

        // A heading is followed by more text, and not by the rest of its own sentence.
        $next = $this->nextLine($lines, $index);

        if ($next === null || Regex::matches('/^\p{Ll}/u', $next)) {
            return false;
        }

        if (mb_strlen($line) > self::HEADING_MAX_CHARACTERS || count(Regex::split('/\s+/u', $line)) > self::HEADING_MAX_WORDS) {
            return false;
        }

        if (str_contains($line, '@') || str_contains($line, '://')) {
            return false;
        }

        // Sentences, list introductions ("… berikut:") and "Label: value" lines are not headings.
        if (Regex::matches('/[.,;:!?]["”’)\]]*$/u', $line) || Regex::matches('/:\s*\S/u', $line)) {
            return false;
        }

        if (Regex::matches('/^[-*•·▪‣◦]\s/u', $line)) {
            return false;
        }

        // Starts with a capital letter, optionally after numbering such as "1.", "2.3", "A." or "IV.".
        return Regex::matches('/^(?:(?:\d{1,3}(?:\.\d{1,3})*|[A-Z]|[IVXLC]{1,6})[.)]?\s+)?\p{Lu}/u', $line);
    }

    /**
     * The next non-blank line, if any.
     *
     * @param  list<array{string, int, int}>  $lines
     */
    private function nextLine(array $lines, int $index): ?string
    {
        for ($next = $index + 1, $count = count($lines); $next < $count; $next++) {
            if ($lines[$next][0] !== '') {
                return $lines[$next][0];
            }
        }

        return null;
    }

    /**
     * Groups segments so that each heading starts a new group. Paragraphs before
     * the first heading form the first group.
     *
     * @param  list<array{heading: string|null, start: int, end: int, lines: list<array{int, int}>}>  $segments
     * @return list<non-empty-list<array{heading: string|null, start: int, end: int, lines: list<array{int, int}>}>>
     */
    private function groupUnderHeadings(array $segments): array
    {
        $groups = [];
        $current = [];

        foreach ($segments as $segment) {
            if ($segment['heading'] !== null && $current !== []) {
                $groups[] = $current;
                $current = [];
            }

            $current[] = $segment;
        }

        if ($current !== []) {
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * Splits a group into parts of at most MAX_SECTION_BYTES, never separating a
     * heading from the text that follows it.
     *
     * @param  non-empty-list<array{heading: string|null, start: int, end: int, lines: list<array{int, int}>}>  $group
     * @return list<non-empty-list<array{start: int, end: int}>>
     */
    private function bounded(array $group): array
    {
        $parts = [];
        $part = [];
        $hasBody = false;

        foreach ($group as $segment) {
            foreach ($this->pieces($segment) as $piece) {
                if ($part !== [] && $hasBody && $piece['end'] - $part[0]['start'] > self::MAX_SECTION_BYTES) {
                    $parts[] = $part;
                    $part = [];
                    $hasBody = false;
                }

                $part[] = $piece;
                $hasBody = $hasBody || $segment['heading'] === null;
            }
        }

        if ($part !== []) {
            $parts[] = $part;
        }

        return $parts;
    }

    /**
     * A segment, or a very long paragraph cut into runs of lines.
     *
     * @param  array{heading: string|null, start: int, end: int, lines: list<array{int, int}>}  $segment
     * @return list<array{start: int, end: int}>
     */
    private function pieces(array $segment): array
    {
        if ($segment['heading'] !== null || $segment['end'] - $segment['start'] <= self::MAX_SECTION_BYTES) {
            return [['start' => $segment['start'], 'end' => $segment['end']]];
        }

        $pieces = [];
        $piece = null;

        foreach ($segment['lines'] as [$start, $end]) {
            if ($piece !== null && $end - $piece['start'] > self::MAX_SECTION_BYTES) {
                $pieces[] = $piece;
                $piece = null;
            }

            $piece = $piece === null ? ['start' => $start, 'end' => $end] : ['start' => $piece['start'], 'end' => $end];
        }

        if ($piece !== null) {
            $pieces[] = $piece;
        }

        return $pieces;
    }
}
