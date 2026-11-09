<?php

declare(strict_types=1);

namespace App\Domain\Document;

use App\Support\Regex;
use SensitiveParameter;

/**
 * Splits a policy into sentences and list items. Leading bullets, numbering
 * and Markdown heading marks are left out, so every unit is a clean, verbatim
 * substring of the policy text that can be quoted as evidence.
 */
final class SentenceSplitter
{
    /** Bullets, numbering and heading marks at the start of a line. */
    private const MARKER = '/^(?:#{1,6}\s+|[-*•·▪‣◦]\s+|\d{1,3}(?:\.\d{1,3})*[.)]\s+|[a-zA-Z][.)]\s+|\([a-zA-Z0-9]{1,4}\)\s+)/u';

    /** Sentence-ending punctuation followed by a space and a capital letter or digit. */
    private const BOUNDARY = '/[.!?]+["”’)\]]*\s+(?=["“‘(\[]?[\p{Lu}\d])/u';

    /** Words that end with a full stop without ending the sentence. */
    private const ABBREVIATIONS = [
        'jl', 'jln', 'no', 'pt', 'cv', 'tbk', 'dll', 'dsb', 'dst', 'dkk', 'hlm', 'tel', 'telp', 'sdr',
        'bpk', 'yth', 'tsb', 'spt', 'mis', 'kec', 'kab', 'prov', 'ps', 'e.g', 'i.e', 'etc', 'inc',
        'ltd', 'co', 'corp', 'dr', 'prof', 'mr', 'mrs', 'ms', 'st', 'vs', 'approx', 'nos', 'art',
        'ref', 'vol', 'ext', 'min', 'maks', 'max',
    ];

    /**
     * @return list<TextUnit>
     */
    public function split(PolicyDocument $document): array
    {
        $units = [];
        $lists = 0;
        $listId = null;

        foreach ($document->sections as $section) {
            $offset = $section->start;
            $lineNumber = 0;

            foreach (explode("\n", $section->text) as $line) {
                $lineStart = $offset;
                $offset += strlen($line) + 1;

                if (trim($line) === '') {
                    continue;
                }

                // A section's heading line is never a list item, even when numbered ("4. Hak Anda").
                $isHeading = $lineNumber++ === 0 && $section->heading !== null && ! $section->continued;
                $skip = $this->markerLength($line);
                $isItem = $skip > 0 && ! $isHeading && ! str_starts_with($line, '#');

                // A list runs over consecutive items; any other line ends it.
                if (! $isItem) {
                    $listId = null;
                }

                $sentences = $this->sentences(substr($line, $skip));
                $introducesList = ! $isItem && str_ends_with(rtrim($line), ':');

                foreach ($sentences as $index => [$sentence, $relative]) {
                    $start = $lineStart + $skip + $relative;
                    $isIntroduction = $introducesList && $index === array_key_last($sentences);

                    $units[] = new TextUnit(
                        sectionId: $section->id,
                        text: $sentence,
                        start: $start,
                        end: $start + strlen($sentence),
                        listId: $isIntroduction ? $lists + 1 : $listId,
                    );
                }

                if ($introducesList) {
                    $listId = ++$lists;
                }
            }
        }

        return $units;
    }

    private function markerLength(#[SensitiveParameter] string $line): int
    {
        $skip = 0;

        // At most two markers, e.g. "## 1. Data yang kami kumpulkan".
        for ($i = 0; $i < 2; $i++) {
            $marker = Regex::first(self::MARKER, substr($line, $skip));

            if ($marker === null) {
                break;
            }

            $skip += strlen($marker[0]);
        }

        return $skip;
    }

    /**
     * @return list<array{string, int}> sentence and byte offset within the line
     */
    private function sentences(#[SensitiveParameter] string $line): array
    {
        $sentences = [];
        $start = 0;

        foreach (Regex::all(self::BOUNDARY, $line) as [$match, $offset]) {
            if ($this->endsWithAbbreviation(substr($line, $start, $offset - $start))) {
                continue;
            }

            $this->push($sentences, $line, $start, $offset + strlen(rtrim($match)));
            $start = $offset + strlen($match);
        }

        $this->push($sentences, $line, $start, strlen($line));

        return $sentences;
    }

    private function endsWithAbbreviation(#[SensitiveParameter] string $text): bool
    {
        $word = Regex::first('/[\p{L}.]+$/u', $text);

        if ($word === null) {
            return false;
        }

        $token = mb_strtolower(trim($word[0], '.'));

        // Single letters are initials ("A. Wijaya") or list letters.
        return mb_strlen($token) === 1 || in_array($token, self::ABBREVIATIONS, true);
    }

    /**
     * @param  list<array{string, int}>  $sentences
     */
    private function push(array &$sentences, #[SensitiveParameter] string $line, int $start, int $end): void
    {
        $piece = substr($line, $start, $end - $start);
        $trimmed = ltrim($piece);
        $leading = strlen($piece) - strlen($trimmed);
        $trimmed = rtrim($trimmed);

        if ($trimmed !== '') {
            $sentences[] = [$trimmed, $start + $leading];
        }
    }
}
