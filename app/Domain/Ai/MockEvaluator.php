<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Document\SentenceSplitter;
use App\Domain\Document\TextUnit;
use App\Domain\Rules\LocalizedList;
use App\Domain\Rules\Rule;
use App\Domain\Rules\RulesetValidator;
use App\Enums\RuleStatus;
use App\Support\Regex;

/**
 * Deterministic offline provider. It judges each rule by keyword matching with
 * the rule's `signals` from the ruleset, and quotes whole sentences from the
 * policy as evidence. It needs no API key and sends nothing anywhere, which
 * makes the app, the tests and the evaluation run fully offline. It is much
 * less accurate than an AI provider and serves as a baseline.
 *
 * For each rule:
 *  - sentences matching any `topic` pattern are about the rule; none → Not found
 *    (a list item is read together with the sentence introducing its list);
 *  - if every `complete` group matches one of those sentences and no `caps`
 *    pattern does → Addressed;
 *  - otherwise → Partially addressed.
 */
final class MockEvaluator implements PolicyEvaluator
{
    public const MODEL = 'mock-keyword-v1';

    private const MAX_QUOTES = 3;

    /** Longer sentences are cut to a window around the first match. */
    private const MAX_QUOTE_BYTES = 480;

    private const REASONS = [
        'addressed' => [
            'Pencocokan kata kunci menemukan pernyataan yang memuat semua unsur yang dicari untuk butir ini.',
            'Keyword matching found statements that contain every element this item looks for.',
        ],
        'partial' => [
            'Pencocokan kata kunci menemukan pernyataan tentang topik ini, tetapi tidak semua unsur yang dicari.',
            'Keyword matching found statements on this topic, but not every element this item looks for.',
        ],
        'capped' => [
            'Pencocokan kata kunci menemukan pernyataan yang mungkin tidak sejalan dengan UU PDP. Lihat kutipan.',
            'Keyword matching found a statement that may not be consistent with the UU PDP. See the quotes.',
        ],
        'not_found' => [
            'Pencocokan kata kunci tidak menemukan pernyataan tentang topik ini.',
            'Keyword matching found no statement on this topic.',
        ],
    ];

    /** @var array<string, string> pattern => compiled regex */
    private array $compiled = [];

    public function __construct(
        private readonly SentenceSplitter $splitter,
    ) {}

    public function name(): string
    {
        return 'mock';
    }

    public function evaluate(EvaluationRequest $request): ProviderResponse
    {
        $units = $this->splitter->split($request->document);

        return new ProviderResponse(
            payload: ['results' => array_map(fn (Rule $rule): array => $this->evaluateRule($rule, $units), $request->rules)],
            model: self::MODEL,
        );
    }

    /**
     * @param  list<TextUnit>  $units
     * @return array{rule_id: string, status: string, reason_id: string, reason_en: string, evidence: list<array{section_id: string, quote: string}>}
     */
    private function evaluateRule(Rule $rule, array $units): array
    {
        $signals = $rule->signals;
        $topic = $this->compile($signals->topic->all());

        $topicIndexes = array_keys(array_filter(
            $units,
            fn (TextUnit $unit): bool => $this->matchesAny($topic, $unit->text),
        ));

        if ($topicIndexes === []) {
            return $this->result($rule, RuleStatus::NotFound, 'not_found', []);
        }

        $related = $this->withListContext($units, $topicIndexes);
        $groups = array_map(fn (LocalizedList $group): array => $this->compile($group->all()), $signals->complete);
        $caps = $this->compile($signals->caps->all());

        /** @var array<int, list<int>> $covers unit index => complete groups it matches */
        $covers = [];
        $satisfied = [];

        foreach ($related as $index) {
            foreach ($groups as $group => $patterns) {
                if ($this->matchesAny($patterns, $units[$index]->text)) {
                    $covers[$index][] = $group;
                    $satisfied[$group] = true;
                }
            }
        }

        $capUnit = null;

        foreach ($caps === [] ? [] : $topicIndexes as $index) {
            if ($this->matchesAny($caps, $units[$index]->text)) {
                $capUnit = $index;

                break;
            }
        }

        [$status, $reason] = match (true) {
            $capUnit !== null => [RuleStatus::PartiallyAddressed, 'capped'],
            count($satisfied) === count($groups) => [RuleStatus::Addressed, 'addressed'],
            default => [RuleStatus::PartiallyAddressed, 'partial'],
        };

        $chosen = $this->chooseEvidence($related, $topicIndexes, $covers, array_keys($satisfied), $capUnit);
        $patterns = [...$topic, ...$caps];

        $evidence = array_map(
            fn (int $index): array => [
                'section_id' => $units[$index]->sectionId,
                'quote' => $this->excerpt($units[$index]->text, $patterns),
            ],
            $chosen,
        );

        return $this->result($rule, $status, $reason, $evidence);
    }

    /**
     * The topic sentences plus the other units of any list they belong to, so
     * a list item is read together with the sentence introducing it (e.g.
     * "Anda berhak:" followed by "b. memperbaiki Data Pribadi …").
     *
     * @param  list<TextUnit>  $units
     * @param  non-empty-list<int>  $topicIndexes
     * @return non-empty-list<int> unit indexes in document order
     */
    private function withListContext(array $units, array $topicIndexes): array
    {
        $lists = [];

        foreach ($topicIndexes as $index) {
            if ($units[$index]->listId !== null) {
                $lists[$units[$index]->listId] = true;
            }
        }

        if ($lists === []) {
            return $topicIndexes;
        }

        $related = [];

        foreach ($units as $index => $unit) {
            if (in_array($index, $topicIndexes, true) || ($unit->listId !== null && isset($lists[$unit->listId]))) {
                $related[] = $index;
            }
        }

        return $related === [] ? $topicIndexes : $related;
    }

    /**
     * Picks up to MAX_QUOTES sentences: the conflicting statement first (if
     * any), then, in document order, the fewest sentences that show every
     * element found. At least one quote is always a sentence about the topic
     * itself, not only its list introduction.
     *
     * @param  non-empty-list<int>  $candidates
     * @param  non-empty-list<int>  $topicIndexes
     * @param  array<int, list<int>>  $covers
     * @param  list<int>  $satisfiedGroups
     * @return non-empty-list<int>
     */
    private function chooseEvidence(array $candidates, array $topicIndexes, array $covers, array $satisfiedGroups, ?int $capUnit): array
    {
        $chosen = [];
        $uncovered = array_diff($satisfiedGroups, $capUnit !== null ? ($covers[$capUnit] ?? []) : []);
        $room = self::MAX_QUOTES - ($capUnit !== null ? 1 : 0);

        while ($uncovered !== [] && count($chosen) < $room) {
            $best = null;
            $bestCount = 0;

            foreach ($candidates as $index) {
                $count = count(array_intersect($covers[$index] ?? [], $uncovered));

                if ($index !== $capUnit && ! in_array($index, $chosen, true) && $count > $bestCount) {
                    $best = $index;
                    $bestCount = $count;
                }
            }

            if ($best === null) {
                break;
            }

            $chosen[] = $best;
            $uncovered = array_diff($uncovered, $covers[$best] ?? []);
        }

        sort($chosen);

        if ($capUnit !== null) {
            return [$capUnit, ...$chosen];
        }

        // Make sure one quote is about the topic itself (the conflicting
        // statement above always is).
        if (array_intersect($chosen, $topicIndexes) === []) {
            $chosen = [...array_slice($chosen, 0, $room - 1), $topicIndexes[0]];
            sort($chosen);
        }

        return $chosen !== [] ? $chosen : [$topicIndexes[0]];
    }

    /**
     * The sentence itself, or a window of it around the first match when it is
     * very long. Either way the quote is a verbatim substring of the policy.
     *
     * @param  list<string>  $patterns
     */
    private function excerpt(string $sentence, array $patterns): string
    {
        if (strlen($sentence) <= self::MAX_QUOTE_BYTES) {
            return $sentence;
        }

        $at = 0;

        foreach ($patterns as $pattern) {
            $match = Regex::first($pattern, $sentence);

            if ($match !== null) {
                $at = $match[1];

                break;
            }
        }

        $start = max(0, $at - 160);
        $end = min(strlen($sentence), $start + self::MAX_QUOTE_BYTES);

        // Cut at spaces so the quote never splits a word or a multibyte character.
        if ($start > 0) {
            $space = strpos($sentence, ' ', $start);
            $start = $space === false ? $start : $space + 1;
        }

        if ($end < strlen($sentence)) {
            $space = strrpos(substr($sentence, 0, $end), ' ');
            $end = $space === false || $space <= $start ? $end : $space;
        }

        return trim(mb_strcut($sentence, $start, $end - $start));
    }

    /**
     * @param  list<array{section_id: string, quote: string}>  $evidence
     * @return array{rule_id: string, status: string, reason_id: string, reason_en: string, evidence: list<array{section_id: string, quote: string}>}
     */
    private function result(Rule $rule, RuleStatus $status, string $reason, array $evidence): array
    {
        return [
            'rule_id' => $rule->id,
            'status' => $status->value,
            'reason_id' => self::REASONS[$reason][0],
            'reason_en' => self::REASONS[$reason][1],
            'evidence' => $evidence,
        ];
    }

    /**
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function compile(array $patterns): array
    {
        return array_map(
            fn (string $pattern): string => $this->compiled[$pattern] ??= RulesetValidator::signalRegex($pattern),
            $patterns,
        );
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesAny(array $patterns, string $text): bool
    {
        foreach ($patterns as $pattern) {
            if (Regex::matches($pattern, $text)) {
                return true;
            }
        }

        return false;
    }
}
