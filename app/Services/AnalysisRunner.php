<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Ai\InvalidProviderOutput;
use App\Domain\Analysis\AnalysisOutcome;
use App\Domain\Analysis\AnalysisPipeline;
use App\Domain\Analysis\RuleOutcome;
use App\Domain\Document\Section;
use App\Domain\Evidence\VerifiedQuote;
use App\Domain\Rules\ContextProfile;
use App\Domain\Rules\RulesetException;
use App\Domain\Rules\RulesetRepository;
use App\Enums\AnalysisError;
use App\Enums\AnalysisStage;
use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Runs a queued analysis through the pipeline and stores the results.
 *
 * Failures are recorded on the analysis as an error code. The log gets the
 * analysis ID, the error code and where it happened, never policy text.
 */
final class AnalysisRunner
{
    public function __construct(
        private readonly AnalysisPipeline $pipeline,
        private readonly RulesetRepository $rulesets,
        private readonly LoggerInterface $logger,
    ) {}

    public function run(string $analysisId): void
    {
        $analysis = Analysis::query()->find($analysisId);

        // Deleted in the meantime, or already picked up by another worker.
        if ($analysis === null || $analysis->status !== AnalysisStatus::Queued) {
            return;
        }

        $analysis->forceFill([
            'status' => AnalysisStatus::Processing,
            'stage' => AnalysisStage::Extracting,
            'progress' => 5,
            'started_at' => now(),
        ])->save();

        try {
            $ruleset = $this->rulesets->version($analysis->ruleset_version);
            $context = ContextProfile::fromInput($ruleset, $analysis->context);
            $text = $analysis->source_text ?? throw new RuntimeException('The analysis has no policy text.');

            $outcome = $this->pipeline->run($text, $ruleset, $context, new StoredProgress($analysis->id));
            $this->store($analysis, $outcome);
        } catch (Throwable $exception) {
            $this->fail($analysis->id, $this->errorFor($exception), $exception);
        }
    }

    /**
     * Marks an unfinished analysis as failed. Finished or deleted analyses are
     * left alone.
     */
    public function fail(string $analysisId, AnalysisError $error, ?Throwable $exception = null): void
    {
        $context = ['analysis' => $analysisId, 'error' => $error->value];

        if ($exception !== null) {
            $context['exception'] = $exception::class;
            $context['location'] = basename($exception->getFile()).':'.$exception->getLine();
        }

        $this->logger->warning('Analysis failed.', $context);

        Analysis::query()
            ->whereKey($analysisId)
            ->whereIn('status', [AnalysisStatus::Queued->value, AnalysisStatus::Processing->value])
            ->update([
                'status' => AnalysisStatus::Failed->value,
                'error_code' => $error->value,
                'completed_at' => now(),
                'expires_at' => now()->addMinutes(config()->integer('cekpdp.retention.minutes')),
            ]);
    }

    private function store(Analysis $analysis, AnalysisOutcome $outcome): void
    {
        $document = $outcome->document;

        $analysis->getConnection()->transaction(function () use ($analysis, $outcome, $document): void {
            // Deleted while it ran ("Delete now", or the purge): discard the results.
            if (! Analysis::query()->whereKey($analysis->id)->exists()) {
                return;
            }

            $analysis->ruleResults()->delete();
            $analysis->ruleResults()->createMany(array_map($this->resultAttributes(...), $outcome->rules));

            $analysis->forceFill([
                'status' => AnalysisStatus::Completed,
                'stage' => AnalysisStage::Done,
                'progress' => 100,
                'progress_meta' => [
                    'provider_requests' => $outcome->providerRequests,
                    'retries' => $outcome->retries,
                ],
                'input_meta' => [
                    ...($analysis->input_meta ?? []),
                    'characters' => $document->characterCount(),
                    'words' => $document->wordCount(),
                    'sections' => count($document->sections),
                    'language' => $document->language->toArray(),
                ],
                'sections' => array_map(static fn (Section $section): array => $section->toArray(), $document->sections),
                'score' => $outcome->score->toArray(),
                'provider' => $outcome->provider,
                'model' => $outcome->models === [] ? null : implode(', ', $outcome->models),
                'completed_at' => now(),
                'expires_at' => now()->addMinutes(config()->integer('cekpdp.retention.minutes')),
            ])->save();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resultAttributes(RuleOutcome $outcome): array
    {
        $resolved = $outcome->resolved;
        $verified = $outcome->evaluation;

        return [
            'rule_id' => $resolved->rule->id,
            'requirement_type' => $resolved->requirementType,
            'applicable' => $resolved->applicable,
            'applied_override' => $resolved->appliedOverride?->key,
            'status' => $verified?->status,
            'model_status' => $verified?->evaluation->status,
            'reason_id' => $verified?->evaluation->reason->id,
            'reason_en' => $verified?->evaluation->reason->en,
            'evidence' => $verified === null ? null : array_map(static fn (VerifiedQuote $quote): array => $quote->toArray(), $verified->quotes),
            'verification' => $verified === null ? null : [
                ...$verified->summary->toArray(),
                'source' => $verified->evaluation->source->value,
            ],
        ];
    }

    private function errorFor(Throwable $exception): AnalysisError
    {
        return match (true) {
            $exception instanceof InvalidProviderOutput => AnalysisError::InvalidProviderOutput,
            $exception instanceof RulesetException => AnalysisError::RulesetUnavailable,
            default => AnalysisError::Internal,
        };
    }
}
