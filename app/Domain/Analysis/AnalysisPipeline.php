<?php

declare(strict_types=1);

namespace App\Domain\Analysis;

use App\Domain\Ai\EvaluationService;
use App\Domain\Document\DocumentBuilder;
use App\Domain\Evidence\EvidenceVerifier;
use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\ResolvedRule;
use App\Domain\Rules\Ruleset;
use App\Domain\Scoring\CoverageScorer;
use App\Enums\AnalysisStage;
use SensitiveParameter;

/**
 * Runs one analysis on cleaned policy text:
 *
 *  1. structure: detect the language and split into sections;
 *  2. decide which rules apply, from the context answers;
 *  3. evaluate the applicable rules with the provider (validated, retried once);
 *  4. verify every quote and downgrade unverifiable results;
 *  5. score deterministically.
 *
 * It uses only the policy text. Nothing here stores or logs it.
 */
final class AnalysisPipeline
{
    public function __construct(
        private readonly DocumentBuilder $documents,
        private readonly ApplicabilityResolver $applicability,
        private readonly EvaluationService $evaluation,
        private readonly EvidenceVerifier $verifier,
        private readonly CoverageScorer $scorer,
    ) {}

    public function run(#[SensitiveParameter] string $cleanText, Ruleset $ruleset, ContextProfile $context, ?ProgressReporter $progress = null): AnalysisOutcome
    {
        $progress?->report(AnalysisStage::Structuring, 15);
        $document = $this->documents->build($cleanText);

        $resolved = $this->applicability->resolve($ruleset, $context);
        $applicable = array_values(array_filter($resolved, static fn (ResolvedRule $rule): bool => $rule->applicable));

        $progress?->report(AnalysisStage::Evaluating, 20);
        $evaluations = $this->evaluation->evaluate(
            $document,
            $applicable,
            $ruleset->version,
            static function (int $done, int $total) use ($progress): void {
                $progress?->report(AnalysisStage::Evaluating, 20 + intdiv(65 * $done, max(1, $total)));
            },
        );

        $progress?->report(AnalysisStage::Verifying, 88);
        $verified = $this->verifier->verifyAll($document, $evaluations->evaluations);

        $progress?->report(AnalysisStage::Scoring, 95);
        $outcomes = array_map(
            static fn (ResolvedRule $rule): RuleOutcome => new RuleOutcome($rule, $rule->applicable ? $verified[$rule->rule->id] : null),
            $resolved,
        );

        return new AnalysisOutcome(
            document: $document,
            rules: $outcomes,
            score: $this->scorer->score($outcomes),
            provider: $evaluations->provider,
            models: $evaluations->models,
            providerRequests: $evaluations->requests,
            retries: $evaluations->retries,
        );
    }
}
