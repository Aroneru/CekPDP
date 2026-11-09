<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use JsonException;

/**
 * Keeps already-validated ruleset data, so a request doesn't parse and validate
 * the YAML file again (about 0.2–0.4 s) when nothing has changed.
 *
 * Entries are keyed by a hash of the ruleset file and the JSON Schema, so any
 * edit to either is validated afresh. Data is kept in memory for the life of
 * the process and as JSON in the cache directory. The cache is optional: if
 * it can't be read or written, rulesets are simply parsed and validated.
 */
final class RulesetCache
{
    /** Bump when validation rules change in code, to invalidate old entries. */
    private const FORMAT = 1;

    /** @var array<string, array<string, mixed>> */
    private static array $memory = [];

    public function __construct(
        private readonly string $directory,
        private readonly string $schemaPath,
    ) {}

    public function key(string $rulesetPath): string
    {
        return hash('sha256', implode("\0", [
            self::FORMAT,
            (string) @file_get_contents($rulesetPath),
            (string) @file_get_contents($this->schemaPath),
        ]));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        if (isset(self::$memory[$key])) {
            return self::$memory[$key];
        }

        $contents = @file_get_contents($this->path($key));

        if ($contents === false) {
            return null;
        }

        try {
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return self::$memory[$key] = $data;
    }

    /**
     * @param  array<string, mixed>  $data  data that has passed validation
     */
    public function put(string $key, array $data): void
    {
        self::$memory[$key] = $data;

        if (! is_dir($this->directory) && ! @mkdir($this->directory, 0755, true) && ! is_dir($this->directory)) {
            return;
        }

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return;
        }

        // Write to a temporary file first so readers never see a partial entry.
        $temporary = $this->path($key).'.'.bin2hex(random_bytes(4)).'.tmp';

        if (@file_put_contents($temporary, $json) !== false && ! @rename($temporary, $this->path($key))) {
            @unlink($temporary);
        }
    }

    /**
     * Forgets the in-memory entries (the files stay).
     */
    public static function flushMemory(): void
    {
        self::$memory = [];
    }

    private function path(string $key): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.$key.'.json';
    }
}
