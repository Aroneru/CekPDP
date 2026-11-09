<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Evaluation\DatasetLoader;
use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\RulesetFactory;
use App\Domain\Rules\RulesetRepository;
use App\Domain\Rules\RulesetValidator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

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
        ));

        $this->app->singleton(DatasetLoader::class, static fn (Application $app): DatasetLoader => new DatasetLoader(
            directory: config()->string('cekpdp.evaluation.directory'),
            resolver: $app->make(ApplicabilityResolver::class),
        ));
    }

    public function boot(): void
    {
        //
    }
}
