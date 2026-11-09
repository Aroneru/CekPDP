<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

use App\Domain\Ai\EvidenceQuote;
use App\Domain\Ai\RuleEvaluation;
use App\Domain\Document\PolicyDocument;
use App\Enums\DowngradeReason;
use App\Enums\RuleStatus;
use App\Support\Regex;

/**
 * Checks that every quote exists word for word in the policy. A quote is looked
 * up in the section the provider cited first, then in the whole policy (the
 * section is corrected and noted).
 *
 * A result becomes Needs human review when any of its quotes can't be found, or
 * when it says the policy covers the item without quoting it. The provider's
 * original status and the failed quotes are kept for transparency.
 *
 * Matching is exact in this version. Tolerance for typographic quotes, dashes
 * and whitespace differences, with an offset map back to the displayed text,
 * comes with the Claude provider in Milestone 3.
 */
final class EvidenceVerifier
{
    /**
     * @param  iterable<RuleEvaluation>  $evaluations
     * @return array<string, VerifiedEvaluation> keyed by rule ID
     */
    public function verifyAll(PolicyDocument $document, iterable $evaluations): array
    {
        $verified = [];

        foreach ($evaluations as $evaluation) {
            $verified[$evaluation->ruleId] = $this->verify($document, $evaluation);
        }

        return $verified;
    }

    public function verify(PolicyDocument $document, RuleEvaluation $evaluation): VerifiedEvaluation
    {
        $quotes = array_map(
            fn (EvidenceQuote $quote): VerifiedQuote => $this->locate($document, $quote),
            $evaluation->evidence,
        );

        $verified = count(array_filter($quotes, static fn (VerifiedQuote $quote): bool => $quote->verified));
        $corrections = count(array_filter($quotes, static fn (VerifiedQuote $quote): bool => $quote->sectionWasCorrected()));

        $downgrade = match (true) {
            $verified < count($quotes) => DowngradeReason::UnverifiedQuote,
            $quotes === [] && $evaluation->status->isFound() && $evaluation->source->requiresEvidence() => DowngradeReason::MissingEvidence,
            default => null,
        };

        return new VerifiedEvaluation(
            evaluation: $evaluation,
            status: $downgrade === null ? $evaluation->status : RuleStatus::NeedsHumanReview,
            quotes: $quotes,
            summary: new VerificationSummary(count($quotes), $verified, $corrections, $downgrade),
        );
    }

    private function locate(PolicyDocument $document, EvidenceQuote $evidence): VerifiedQuote
    {
        $quote = $evidence->quote;

        // A quote must contain real text; whitespace or punctuation alone proves nothing.
        if (! Regex::matches('/[\p{L}\p{N}]/u', $quote)) {
            return new VerifiedQuote($quote, $evidence->sectionId, false);
        }

        $section = $document->section($evidence->sectionId);
        $position = $section === null ? false : strpos($section->text, $quote);

        if ($section !== null && $position !== false) {
            $start = $section->start + $position;

            return new VerifiedQuote($quote, $evidence->sectionId, true, $section->id, $start, $start + strlen($quote));
        }

        $position = strpos($document->text, $quote);

        if ($position === false) {
            return new VerifiedQuote($quote, $evidence->sectionId, false);
        }

        return new VerifiedQuote(
            quote: $quote,
            citedSectionId: $evidence->sectionId,
            verified: true,
            sectionId: $document->sectionAt($position)?->id,
            start: $position,
            end: $position + strlen($quote),
        );
    }
}
