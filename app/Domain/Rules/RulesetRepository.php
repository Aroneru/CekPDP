<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads versioned ruleset files from resources/rulesets/{id}/{version}.yaml.
 * Every file is validated before use, so an invalid ruleset can never reach an
 * analysis. Old versions stay loadable so past analyses keep their meaning.
 */
final class RulesetRepository
{
    private const VERSION_PATTERN = '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/';

    /** @var array<string, Ruleset> */
    private array $loaded = [];

    public function __construct(
        private readonly RulesetValidator $validator,
        private readonly RulesetFactory $factory,
        private readonly string $directory,
        private readonly string $rulesetId,
        private readonly string $activeVersion,
        private readonly ?RulesetCache $cache = null,
    ) {}

    public function active(): Ruleset
    {
        return $this->version($this->activeVersion);
    }

    public function activeVersion(): string
    {
        return $this->activeVersion;
    }

    public function version(string $version): Ruleset
    {
        if (preg_match(self::VERSION_PATTERN, $version) !== 1) {
            throw RulesetException::invalidVersion($version);
        }

        return $this->loaded[$version] ??= $this->load($this->pathFor($version));
    }

    /**
     * @return list<string> versions with a ruleset file, oldest first
     */
    public function availableVersions(): array
    {
        $versions = array_map(
            static fn (string $path): string => basename($path, '.yaml'),
            glob($this->directory.DIRECTORY_SEPARATOR.$this->rulesetId.DIRECTORY_SEPARATOR.'*.yaml') ?: [],
        );

        $versions = array_values(array_filter(
            $versions,
            static fn (string $version): bool => preg_match(self::VERSION_PATTERN, $version) === 1,
        ));

        usort($versions, static fn (string $a, string $b): int => version_compare($a, $b));

        return $versions;
    }

    public function pathFor(string $version): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.$this->rulesetId.DIRECTORY_SEPARATOR.$version.'.yaml';
    }

    /**
     * Parses and validates a ruleset file without building it, e.g. for the
     * validation command.
     */
    public function check(string $path): RulesetValidationResult
    {
        return $this->validated($path)[1];
    }

    private function load(string $path): Ruleset
    {
        if (! is_file($path)) {
            throw RulesetException::notFound($path);
        }

        if ($this->cache === null) {
            return $this->factory->fromArray($this->validData($path));
        }

        $key = $this->cache->key($path);
        $data = $this->cache->get($key);

        if ($data === null) {
            $data = $this->validData($path);
            $this->cache->put($key, $data);
        }

        return $this->factory->fromArray($data);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RulesetException when the file is invalid
     */
    private function validData(string $path): array
    {
        [$data, $result] = $this->validated($path);

        if (! $result->isValid()) {
            throw RulesetException::invalid($path, $result->errors);
        }

        return $data;
    }

    /**
     * @return array{array<string, mixed>, RulesetValidationResult}
     */
    private function validated(string $path): array
    {
        $data = $this->parse($path);
        $result = $this->validator->validate($data);

        if (! $result->isValid()) {
            return [$data, $result];
        }

        $expectedVersion = basename($path, '.yaml');
        $errors = [];

        if ($data['id'] !== $this->rulesetId) {
            $errors[] = "ruleset id must be {$this->rulesetId}";
        }

        if ($data['version'] !== $expectedVersion) {
            $errors[] = "version must match the file name ({$expectedVersion})";
        }

        return [$data, new RulesetValidationResult($errors, $result->warnings)];
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $path): array
    {
        if (! is_file($path)) {
            throw RulesetException::notFound($path);
        }

        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw RulesetException::unreadable($path, $exception->getMessage());
        }

        if (! is_array($data)) {
            throw RulesetException::unreadable($path, 'the file does not contain a mapping');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
