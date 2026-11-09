<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Document\LanguageProfile;
use App\Domain\Evidence\VerificationSummary;
use App\Domain\Evidence\VerifiedQuote;
use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\LocalizedText;
use App\Domain\Rules\Provision;
use App\Domain\Rules\Rule;
use App\Domain\Rules\Ruleset;
use App\Domain\Rules\RulesetRepository;
use App\Domain\Rules\Source;
use App\Domain\Scoring\CoverageScore;
use App\Enums\EvaluationSource;
use App\Enums\RequirementType;
use App\Models\Analysis;
use App\Models\RuleResult;

/**
 * Builds the report for a completed analysis from its stored results and the
 * ruleset version it used.
 */
final class ReportBuilder
{
    public function __construct(
        private readonly RulesetRepository $rulesets,
    ) {}

    public function build(Analysis $analysis): Report
    {
        $ruleset = $this->rulesets->version($analysis->ruleset_version);
        $context = ContextProfile::fromInput($ruleset, $analysis->context);
        $results = $analysis->ruleResults->keyBy('rule_id');

        $legal = [];
        $bestPractices = [];
        $notApplicable = [];

        foreach ($ruleset->activeRules() as $rule) {
            $result = $results->get($rule->id);

            if (! $result instanceof RuleResult) {
                continue;
            }

            $item = $this->item($ruleset, $context, $rule, $result);

            match (true) {
                ! $item->applicable => $notApplicable[] = $item,
                $item->requirementType === RequirementType::Legal => $legal[] = $item,
                default => $bestPractices[] = $item,
            };
        }

        $meta = $analysis->input_meta ?? [];
        $int = static fn (string $key): int => is_int($meta[$key] ?? null) ? $meta[$key] : 0;

        return new Report(
            analysisId: $analysis->id,
            ruleset: $ruleset,
            score: CoverageScore::fromArray($analysis->score ?? []),
            language: LanguageProfile::fromArray(is_array($meta['language'] ?? null) ? $meta['language'] : []),
            characters: $int('characters'),
            words: $int('words'),
            sections: $int('sections'),
            provider: $analysis->provider,
            model: $analysis->model,
            completedAt: $analysis->completed_at,
            expiresAt: $analysis->expires_at,
            context: array_map(
                static fn (string $key): ReportContextAnswer => new ReportContextAnswer($ruleset->contexts[$key], $context->answer($key)),
                array_keys($ruleset->contexts),
            ),
            legal: $legal,
            bestPractices: $bestPractices,
            notApplicable: $notApplicable,
        );
    }

    private function item(Ruleset $ruleset, ContextProfile $context, Rule $rule, RuleResult $result): ReportItem
    {
        $hasReason = $result->reason_id !== null && $result->reason_en !== null;

        return new ReportItem(
            rule: $rule,
            applicable: $result->applicable,
            requirementType: $result->requirement_type,
            override: $result->applied_override !== null && isset($ruleset->overrides[$result->applied_override])
                ? $ruleset->overrides[$result->applied_override]
                : null,
            status: $result->status,
            modelStatus: $result->model_status,
            reason: $hasReason ? new LocalizedText((string) $result->reason_id, (string) $result->reason_en) : null,
            quotes: array_values(array_map(
                static fn (mixed $quote): VerifiedQuote => VerifiedQuote::fromArray(is_array($quote) ? $quote : []),
                $result->evidence ?? [],
            )),
            verification: $result->verification === null ? null : VerificationSummary::fromArray($result->verification),
            source: is_string($result->verification['source'] ?? null) ? EvaluationSource::tryFrom($result->verification['source']) : null,
            references: $this->references($ruleset, $rule),
            notApplicableBecause: $result->applicable ? [] : $this->notApplicableBecause($ruleset, $context, $rule),
        );
    }

    /**
     * @return list<ReportReference>
     */
    private function references(Ruleset $ruleset, Rule $rule): array
    {
        $reader = null;

        foreach ($ruleset->sources as $source) {
            if (! $source->isOfficial() && $source->urlTemplate !== null) {
                $reader = $source;

                break;
            }
        }

        $references = [];

        foreach ($rule->references as $reference) {
            $provision = $ruleset->provision($reference->provisionKey);
            $source = $ruleset->source($provision->sourceKey);

            $references[] = new ReportReference(
                provision: $provision,
                role: $reference->role,
                pinpoint: $reference->pinpoint,
                source: $source,
                officialUrl: $this->pageUrl($source, $provision),
                readerUrl: $reader?->articleUrl($provision->article),
            );
        }

        return $references;
    }

    /**
     * Links straight to the provision's page in the official PDF.
     */
    private function pageUrl(Source $source, Provision $provision): ?string
    {
        if ($source->documentUrl === null) {
            return $source->url;
        }

        $page = (int) explode('-', $provision->pdfPage)[0];

        return $page > 0 ? "{$source->documentUrl}#page={$page}" : $source->documentUrl;
    }

    /**
     * @return list<ReportContextAnswer>
     */
    private function notApplicableBecause(Ruleset $ruleset, ContextProfile $context, Rule $rule): array
    {
        $answers = [];

        foreach ($rule->applicability->anyOf as $condition) {
            if (isset($ruleset->contexts[$condition->context])) {
                $answers[$condition->context] = new ReportContextAnswer(
                    $ruleset->contexts[$condition->context],
                    $context->answer($condition->context),
                );
            }
        }

        return array_values($answers);
    }
}
