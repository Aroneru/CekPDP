<?php

declare(strict_types=1);

use App\Domain\Evaluation\DatasetException;
use App\Domain\Evaluation\DatasetLoader;
use App\Domain\Rules\ApplicabilityResolver;
use App\Domain\Rules\RulesetRepository;
use App\Enums\RuleStatus;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

beforeEach(function (): void {
    $this->ruleset = app(RulesetRepository::class)->active();
});

describe('the bundled dataset', function (): void {
    beforeEach(function (): void {
        $this->cases = app(DatasetLoader::class)->load($this->ruleset);
    });

    it('has at least ten fictional policies in Indonesian, English and both', function (): void {
        $languages = array_count_values(array_map(fn ($case) => $case->language, $this->cases));

        expect($this->cases)->toHaveCount(12)
            ->and($languages)->toBe(['mixed' => 1, 'en' => 5, 'id' => 6]);
    });

    it('expects every status somewhere, so the evaluation can measure each one', function (): void {
        $statuses = [];

        foreach ($this->cases as $case) {
            foreach ($case->applicableResults() as $result) {
                $statuses[$result->status?->value] = true;
            }
        }

        expect(array_keys($statuses))->toContain(
            RuleStatus::Addressed->value,
            RuleStatus::PartiallyAddressed->value,
            RuleStatus::NotFound->value,
        );
    });

    it('uses only reserved example domains for email addresses', function (): void {
        foreach ($this->cases as $case) {
            preg_match_all('/[\w.+-]+@([\w-]+(?:\.[\w-]+)+)/', $case->policyText(), $matches);

            foreach ($matches[1] as $domain) {
                expect($domain)->toEndWith('.example');
            }
        }
    });
});

describe('validation', function (): void {
    beforeEach(function (): void {
        $this->directory = storage_path('framework/testing/dataset-'.uniqid());
        File::copyDirectory(base_path('evaluation/fixtures'), $this->directory.'/fixtures');
        File::ensureDirectoryExists($this->directory.'/expected');
        File::copy(base_path('evaluation/expected/id-09-umkm-minim.yaml'), $this->directory.'/expected/id-09-umkm-minim.yaml');

        $this->loader = new DatasetLoader($this->directory, app(ApplicabilityResolver::class));
        $this->expectedPath = $this->directory.'/expected/id-09-umkm-minim.yaml';
    });

    afterEach(function (): void {
        File::deleteDirectory($this->directory);
    });

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $change
     */
    function changeExpectations(string $path, callable $change): void
    {
        file_put_contents($path, Yaml::dump($change(Yaml::parseFile($path)), 4));
    }

    it('loads a valid copy', function (): void {
        expect($this->loader->load($this->ruleset))->toHaveCount(1);
    });

    it('reports every problem with the expectations', function (callable $change, string $message): void {
        changeExpectations($this->expectedPath, $change);

        expect(fn () => $this->loader->load($this->ruleset))->toThrow(DatasetException::class, $message);
    })->with([
        'missing rule' => [function (array $data) {
            unset($data['expected']['PDP-SEC-01']);

            return $data;
        }, 'PDP-SEC-01: no expected result'],
        'applicable rule marked not applicable' => [function (array $data) {
            $data['expected']['PDP-SEC-01'] = 'not_applicable';

            return $data;
        }, 'PDP-SEC-01: marked not_applicable but the rule applies'],
        'inapplicable rule given a status' => [function (array $data) {
            $data['expected']['PDP-CHILD-01'] = 'not_found';

            return $data;
        }, 'PDP-CHILD-01: must be not_applicable'],
        'unknown status' => [function (array $data) {
            $data['expected']['PDP-SEC-01'] = 'great';

            return $data;
        }, 'PDP-SEC-01: unknown status great'],
        'alternative without a note' => [function (array $data) {
            $data['expected']['PDP-SEC-01'] = ['status' => 'not_found', 'acceptable' => ['partially_addressed']];

            return $data;
        }, 'acceptable alternatives need a note'],
        'unknown rule' => [function (array $data) {
            $data['expected']['PDP-NOPE-01'] = 'addressed';

            return $data;
        }, 'PDP-NOPE-01: not an active rule'],
        'wrong ruleset version' => [function (array $data) {
            $data['ruleset_version'] = '0.9.0';

            return $data;
        }, 'ruleset_version must be 1.0.0'],
        'invalid context answer' => [function (array $data) {
            $data['context']['children'] = 'maybe';

            return $data;
        }, 'Invalid answer for context question children'],
    ]);

    it('requires the policy text to exist', function (): void {
        File::delete($this->directory.'/fixtures/id-09-umkm-minim.md');

        expect(fn () => $this->loader->load($this->ruleset))
            ->toThrow(DatasetException::class, 'fixture file missing or empty');
    });
});
