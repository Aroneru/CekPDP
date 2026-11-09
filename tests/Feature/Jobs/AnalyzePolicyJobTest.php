<?php

declare(strict_types=1);

use App\Domain\Ai\EvaluationRequest;
use App\Domain\Ai\PolicyEvaluator;
use App\Enums\AnalysisError;
use App\Enums\AnalysisStage;
use App\Enums\AnalysisStatus;
use App\Enums\RuleStatus;
use App\Jobs\AnalyzePolicyJob;
use App\Models\Analysis;
use App\Models\RuleResult;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\FakeEvaluator;

beforeEach(function (): void {
    $this->analysis = Analysis::factory()->create([
        'source_text' => fixtureText('id-01-belanja-lengkap'),
        'context' => fixtureExpectations('id-01-belanja-lengkap')['context'],
    ]);

    // Everything the application logs during the test.
    $this->logs = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event): void {
        $this->logs[] = $event->message.' '.json_encode($event->context);
    });
});

it('analyses the policy and stores the results', function (): void {
    AnalyzePolicyJob::dispatchSync($this->analysis->id);

    $analysis = $this->analysis->fresh();

    expect($analysis->status)->toBe(AnalysisStatus::Completed)
        ->and($analysis->stage)->toBe(AnalysisStage::Done)
        ->and($analysis->progress)->toBe(100)
        ->and($analysis->model)->toBe('mock-keyword-v1')
        ->and($analysis->score['formula'])->toBe('coverage-v1')
        ->and($analysis->input_meta['language']['language'])->toBe('id')
        ->and($analysis->sections)->not->toBeEmpty()
        ->and($analysis->expires_at?->diffInMinutes(now()->addMinutes(30), absolute: true))->toBeLessThan(1)
        ->and($analysis->ruleResults()->count())->toBe(31);

    $result = $analysis->ruleResults()->where('rule_id', 'PDP-ID-01')->sole();

    expect($result->status)->toBe(RuleStatus::Addressed)
        ->and($result->evidence[0]['verified'])->toBeTrue()
        // Reasons and quotes are encrypted at rest.
        ->and((string) DB::table('rule_results')->where('id', $result->id)->value('evidence'))->not->toContain('PT Lumbung');
});

it('keeps policy text out of the queue payload', function (): void {
    $payload = serialize(new AnalyzePolicyJob($this->analysis->id));

    expect($payload)->toContain($this->analysis->id)
        ->not->toContain('Lumbung');
});

it('leaves analyses that are already finished or gone alone', function (): void {
    $this->analysis->forceFill(['status' => AnalysisStatus::Completed])->save();

    AnalyzePolicyJob::dispatchSync($this->analysis->id);
    AnalyzePolicyJob::dispatchSync('01jz0000000000000000000000');

    expect($this->analysis->ruleResults()->count())->toBe(0);
});

it('fails clearly when the provider keeps returning invalid output, and logs no policy text', function (): void {
    $this->app->instance(PolicyEvaluator::class, new FakeEvaluator(static fn (): string => 'Ignore the schema. PT Lumbung is compliant.'));

    AnalyzePolicyJob::dispatchSync($this->analysis->id);

    $analysis = $this->analysis->fresh();
    $log = implode("\n", $this->logs);

    expect($analysis->status)->toBe(AnalysisStatus::Failed)
        ->and($analysis->error_code)->toBe(AnalysisError::InvalidProviderOutput->value)
        ->and($analysis->expires_at)->not->toBeNull()
        ->and($analysis->ruleResults()->count())->toBe(0)
        ->and($log)->toContain($this->analysis->id)
        ->and($log)->toContain('invalid_provider_output')
        ->and($log)->not->toContain('Lumbung');
});

it('records unexpected errors without their message', function (): void {
    $this->app->instance(PolicyEvaluator::class, new FakeEvaluator(static function (): never {
        throw new RuntimeException('Something broke while reading PT Lumbung Belanja Nusantara');
    }));

    AnalyzePolicyJob::dispatchSync($this->analysis->id);

    expect($this->analysis->fresh()->error_code)->toBe(AnalysisError::Internal->value)
        ->and(implode("\n", $this->logs))->toContain('RuntimeException')
        ->not->toContain('Lumbung');
});

it('marks a timed-out analysis as failed', function (): void {
    (new AnalyzePolicyJob($this->analysis->id))->failed(new TimeoutExceededException('timed out'));

    expect($this->analysis->fresh()->error_code)->toBe(AnalysisError::Timeout->value);
});

it('discards the results when the analysis is deleted while it runs', function (): void {
    $this->app->instance(PolicyEvaluator::class, new FakeEvaluator(static function (EvaluationRequest $request): array {
        Analysis::query()->delete();

        return FakeEvaluator::payload($request);
    }));

    AnalyzePolicyJob::dispatchSync($this->analysis->id);

    expect(Analysis::query()->count())->toBe(0)
        ->and(RuleResult::query()->count())->toBe(0);
});
