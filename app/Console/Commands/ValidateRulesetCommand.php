<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Rules\Ruleset;
use App\Domain\Rules\RulesetException;
use App\Domain\Rules\RulesetRepository;
use App\Enums\LegalMapping;
use App\Enums\RequirementType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cekpdp:ruleset:validate {version? : Validate one version (default: every version file)}')]
#[Description('Validate ruleset files against the JSON Schema and the semantic rules')]
final class ValidateRulesetCommand extends Command
{
    public function handle(RulesetRepository $repository): int
    {
        $requested = $this->argument('version');
        $versions = is_string($requested) ? [$requested] : $repository->availableVersions();

        if ($versions === []) {
            $this->error('No ruleset files found.');

            return self::FAILURE;
        }

        $failed = false;

        foreach ($versions as $version) {
            $failed = ! $this->validateVersion($repository, $version) || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function validateVersion(RulesetRepository $repository, string $version): bool
    {
        try {
            $result = $repository->check($repository->pathFor($version));
        } catch (RulesetException $exception) {
            $this->error($exception->getMessage());

            return false;
        }

        foreach ($result->warnings as $warning) {
            $this->warn("  warning: {$warning}");
        }

        if (! $result->isValid()) {
            $this->error("Ruleset {$version} is invalid:");

            foreach ($result->errors as $error) {
                $this->line("  - {$error}");
            }

            return false;
        }

        $this->summarise($repository->version($version), $version === $repository->activeVersion());

        return true;
    }

    private function summarise(Ruleset $ruleset, bool $isActiveVersion): void
    {
        $this->info(sprintf(
            'Ruleset %s %s is valid. Status: %s; last reviewed %s%s.',
            $ruleset->id,
            $ruleset->version,
            $ruleset->status->value,
            $ruleset->lastReviewed->toDateString(),
            $isActiveVersion ? '; used for new analyses' : '',
        ));

        $rows = [
            'legal-direct' => ['Legal', 'direct', 0, 0],
            'legal-interpretive' => ['Legal', 'interpretive', 0, 0],
            'best-practice' => ['Best practice', '-', 0, 0],
        ];
        $basisProvisions = [];

        foreach ($ruleset->rules as $rule) {
            $row = match (true) {
                $rule->requirementType === RequirementType::BestPractice => 'best-practice',
                $rule->legalMapping === LegalMapping::Interpretive => 'legal-interpretive',
                default => 'legal-direct',
            };

            $rows[$row][$rule->isActive() ? 2 : 3]++;

            foreach ($rule->basisReferences() as $reference) {
                $basisProvisions[$reference->provisionKey] = true;
            }
        }

        $this->table(['Type', 'Mapping', 'Active', 'Needs review'], array_values($rows));

        $this->line(sprintf(
            '  %d provisions (%d cited as a basis), %d context questions, %d court decision(s), %d implementing regulation(s) recorded.',
            count($ruleset->provisions),
            count($basisProvisions),
            count($ruleset->contexts),
            count($ruleset->instrument->courtDecisions),
            count($ruleset->instrument->implementingRegulations),
        ));
    }
}
