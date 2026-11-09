<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\ContextAnswer;
use App\Enums\LegalMapping;
use App\Enums\ReferenceRole;
use App\Enums\RequirementType;
use App\Enums\RuleEvaluator;
use App\Enums\RuleReviewStatus;
use App\Enums\RulesetStatus;
use Carbon\CarbonImmutable;

/**
 * Maps ruleset data that has passed RulesetValidator onto value objects.
 * It assumes valid input; never call it with unvalidated data.
 */
final class RulesetFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): Ruleset
    {
        return new Ruleset(
            id: $this->string($data, 'id'),
            version: $this->string($data, 'version'),
            status: RulesetStatus::from($this->string($data, 'status')),
            lastReviewed: $this->date($data, 'last_reviewed'),
            review: $this->review($this->map($data, 'review')),
            scope: $this->text($this->map($data, 'scope')),
            instrument: $this->instrument($this->map($data, 'instrument')),
            sources: $this->keyed($this->map($data, 'sources'), $this->source(...)),
            contexts: $this->keyed($this->map($data, 'contexts'), $this->context(...)),
            overrides: $this->keyed($this->map($data, 'overrides'), $this->override(...)),
            provisions: $this->keyed($this->map($data, 'provisions'), $this->provision(...)),
            rules: array_map($this->rule(...), $this->maps($data, 'rules')),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function review(array $data): RulesetReview
    {
        return new RulesetReview(
            verifiedBy: $this->string($data, 'verified_by'),
            verifiedOn: $this->date($data, 'verified_on'),
            approvedOn: isset($data['approved_on']) ? $this->date($data, 'approved_on') : null,
            approvalNote: isset($data['approval_note']) ? $this->text($this->map($data, 'approval_note')) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function instrument(array $data): Instrument
    {
        return new Instrument(
            key: $this->string($data, 'key'),
            title: new LocalizedText($this->string($data, 'title_id'), $this->string($data, 'title_en')),
            enactedOn: $this->date($data, 'enacted_on'),
            promulgatedOn: $this->date($data, 'promulgated_on'),
            inForceFrom: $this->date($data, 'in_force_from'),
            transitionEndedOn: $this->date($data, 'transition_ended_on'),
            gazette: $this->string($data, 'gazette'),
            courtDecisions: array_map(
                fn (array $decision): CourtDecision => new CourtDecision(
                    reference: $this->string($decision, 'ref'),
                    pronouncedOn: $this->date($decision, 'pronounced_on'),
                    affectsProvision: $this->string($decision, 'affects'),
                    effect: $this->text($this->map($decision, 'effect')),
                    sourceKey: $this->string($decision, 'source'),
                    verifiedOn: $this->date($decision, 'verified_on'),
                ),
                $this->maps($data, 'court_decisions'),
            ),
            implementingRegulations: array_map(
                fn (array $regulation): ImplementingRegulation => new ImplementingRegulation(
                    reference: $this->string($regulation, 'ref'),
                    status: $this->string($regulation, 'status'),
                    note: $this->text($this->map($regulation, 'note')),
                    checkedOn: $this->date($regulation, 'checked_on'),
                    usedByRules: (bool) ($regulation['used_by_rules'] ?? false),
                ),
                $this->maps($data, 'implementing_regulations'),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function source(string $key, array $data): Source
    {
        return new Source(
            key: $key,
            kind: $this->string($data, 'kind'),
            name: $this->string($data, 'name'),
            url: $this->optionalString($data, 'url'),
            documentUrl: $this->optionalString($data, 'document_url'),
            sha256: $this->optionalString($data, 'sha256'),
            urlTemplate: $this->optionalString($data, 'url_template'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function context(string $key, array $data): ContextQuestion
    {
        return new ContextQuestion(
            key: $key,
            question: $this->text($this->map($data, 'question')),
            help: isset($data['help']) ? $this->text($this->map($data, 'help')) : null,
            options: $this->answers($data, 'options'),
            default: ContextAnswer::from($this->string($data, 'default')),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function override(string $key, array $data): TypeOverride
    {
        return new TypeOverride(
            key: $key,
            when: $this->condition($this->map($data, 'when')),
            requirementType: RequirementType::from($this->string($data, 'requirement_type')),
            reason: $this->text($this->map($data, 'reason')),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function provision(string $key, array $data): Provision
    {
        return new Provision(
            key: $key,
            citation: new LocalizedText($this->string($data, 'citation_id'), $this->string($data, 'citation_en')),
            article: $this->int($data, 'article'),
            pdfPage: $this->string($data, 'pdf_page'),
            text: new LocalizedText($this->string($data, 'text_id'), $this->string($data, 'text_en')),
            sourceKey: $this->string($data, 'source'),
            verifiedOn: $this->date($data, 'verified_on'),
            modifiedBy: $this->optionalString($data, 'modified_by'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rule(array $data): Rule
    {
        $criteria = $this->map($data, 'criteria');

        return new Rule(
            id: $this->string($data, 'id'),
            status: RuleReviewStatus::from($this->string($data, 'status')),
            category: $this->string($data, 'category'),
            requirementType: RequirementType::from($this->string($data, 'requirement_type')),
            legalMapping: isset($data['legal_mapping']) ? LegalMapping::from($this->string($data, 'legal_mapping')) : null,
            interpretationNote: isset($data['interpretation_note']) ? $this->text($this->map($data, 'interpretation_note')) : null,
            title: $this->text($this->map($data, 'title')),
            explanation: $this->text($this->map($data, 'explanation')),
            references: array_map(
                fn (array $reference): RuleReference => new RuleReference(
                    provisionKey: $this->string($reference, 'provision'),
                    role: ReferenceRole::from($this->string($reference, 'role')),
                    pinpoint: isset($reference['pinpoint']) ? $this->text($this->map($reference, 'pinpoint')) : null,
                ),
                $this->maps($data, 'references'),
            ),
            applicability: $this->applicability($data['applies_when'] ?? 'always'),
            typeOverrides: $this->strings($data, 'type_overrides'),
            lookFor: $this->list($this->map($data, 'look_for')),
            criteria: new RuleCriteria(
                addressed: $this->text($this->map($criteria, 'addressed')),
                partiallyAddressed: $this->text($this->map($criteria, 'partially_addressed')),
                notFound: $this->text($this->map($criteria, 'not_found')),
            ),
            nextStep: $this->text($this->map($data, 'next_step')),
            exampleWording: $this->text($this->map($data, 'example_wording')),
            evaluator: RuleEvaluator::from($this->optionalString($data, 'evaluator') ?? RuleEvaluator::Ai->value),
            signals: $this->signals($this->map($data, 'signals')),
        );
    }

    private function applicability(mixed $value): Applicability
    {
        if (! is_array($value)) {
            return Applicability::always();
        }

        /** @var array<string, mixed> $value */
        return Applicability::anyOf(array_map($this->condition(...), $this->maps($value, 'any')));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function condition(array $data): ContextCondition
    {
        return new ContextCondition($this->string($data, 'context'), $this->answers($data, 'in'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function signals(array $data): RuleSignals
    {
        return new RuleSignals(
            topic: $this->list($this->map($data, 'topic')),
            complete: array_map($this->list(...), $this->maps($data, 'complete')),
            caps: $this->list($this->map($data, 'caps')),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function text(array $data): LocalizedText
    {
        return new LocalizedText($this->string($data, 'id'), $this->string($data, 'en'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function list(array $data): LocalizedList
    {
        return new LocalizedList($this->strings($data, 'id'), $this->strings($data, 'en'));
    }

    /**
     * @template T
     *
     * @param  array<string, mixed>  $data
     * @param  callable(string, array<string, mixed>): T  $build
     * @return array<string, T>
     */
    private function keyed(array $data, callable $build): array
    {
        $items = [];

        foreach ($data as $key => $value) {
            /** @var array<string, mixed> $value */
            $items[(string) $key] = $build((string) $key, $value);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<ContextAnswer>
     */
    private function answers(array $data, string $key): array
    {
        return array_map(
            static fn (string $value): ContextAnswer => ContextAnswer::from($value),
            $this->strings($data, $key),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function map(array $data, string $key): array
    {
        /** @var array<string, mixed> */
        return is_array($data[$key] ?? null) ? $data[$key] : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function maps(array $data, string $key): array
    {
        /** @var list<array<string, mixed>> */
        return is_array($data[$key] ?? null) ? array_values($data[$key]) : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function strings(array $data, string $key): array
    {
        $values = is_array($data[$key] ?? null) ? $data[$key] : [];

        return array_values(array_map(static fn (mixed $value): string => (string) $value, $values));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function string(array $data, string $key): string
    {
        return (string) $data[$key];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalString(array $data, string $key): ?string
    {
        return isset($data[$key]) ? (string) $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function int(array $data, string $key): int
    {
        return (int) $data[$key];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function date(array $data, string $key): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string($data, $key), 'UTC')
            ?: throw new RulesetException("Invalid date for {$key}");
    }
}
