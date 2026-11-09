<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ai\EvaluationService;
use App\Domain\Ai\LanguageCheck;
use App\Domain\Ai\MockEvaluator;
use App\Domain\Ai\PolicyEvaluator;
use App\Domain\Ai\ResponseValidator;
use App\Domain\Evaluation\DatasetLoader;
use App\Domain\Evaluation\SamplePolicies;
use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\RulesetCache;
use App\Domain\Rules\RulesetFactory;
use App\Domain\Rules\RulesetRepository;
use App\Domain\Rules\RulesetValidator;
use App\Http\Support\VisitorKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RulesetValidator::class, static fn (): RulesetValidator => new RulesetValidator(
            config()->string('cekpdp.ruleset.schema'),
        ));

        $this->app->singleton(RulesetRepository::class, static fn (Application $app): RulesetRepository => new RulesetRepository(
            validator: $app->make(RulesetValidator::class),
            factory: new RulesetFactory,
            directory: config()->string('cekpdp.ruleset.directory'),
            rulesetId: config()->string('cekpdp.ruleset.id'),
            activeVersion: config()->string('cekpdp.ruleset.active_version'),
            cache: new RulesetCache(
                directory: config()->string('cekpdp.ruleset.cache_directory'),
                schemaPath: config()->string('cekpdp.ruleset.schema'),
            ),
        ));

        $this->app->singleton(DatasetLoader::class, static fn (Application $app): DatasetLoader => new DatasetLoader(
            directory: config()->string('cekpdp.evaluation.directory'),
            resolver: $app->make(ApplicabilityResolver::class),
        ));

        $this->app->singleton(SamplePolicies::class, static fn (): SamplePolicies => new SamplePolicies(
            directory: config()->string('cekpdp.evaluation.directory'),
            slugs: array_values(array_filter((array) config('cekpdp.samples'), is_string(...))),
        ));

        $this->app->singleton(ResponseValidator::class, static fn (): ResponseValidator => new ResponseValidator(
            config()->string('cekpdp.ai.response_schema'),
        ));

        $this->app->bind(PolicyEvaluator::class, static fn (Application $app): PolicyEvaluator => match ($name = config()->string('cekpdp.ai.provider')) {
            'mock' => $app->make(MockEvaluator::class),
            default => throw new InvalidArgumentException("Unknown AI provider \"{$name}\". Set CEKPDP_AI_PROVIDER to \"mock\"."),
        });

        $this->app->bind(EvaluationService::class, static fn (Application $app): EvaluationService => new EvaluationService(
            provider: $app->make(PolicyEvaluator::class),
            validator: $app->make(ResponseValidator::class),
            languageCheck: $app->make(LanguageCheck::class),
            batchSize: config()->integer('cekpdp.ai.batch_size'),
        ));
    }

    public function boot(): void
    {
        // A burst limit on form posts. The hourly limit on accepted analyses is
        // applied in AnalysisController, after validation.
        RateLimiter::for('submissions', static fn (Request $request): Limit => Limit::perMinute(20)->by(VisitorKey::for($request)));

        // `composer dev` also runs the scheduler, which deletes expired analyses.
        DevCommands::artisan('schedule:work', 'scheduler');
    }
}
