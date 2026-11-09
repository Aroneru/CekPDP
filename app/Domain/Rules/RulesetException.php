<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use RuntimeException;

final class RulesetException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public static function notFound(string $path): self
    {
        return new self("Ruleset file not found: {$path}");
    }

    public static function unreadable(string $path, string $reason): self
    {
        return new self("Ruleset file could not be parsed: {$path} ({$reason})");
    }

    /**
     * @param  list<string>  $errors
     */
    public static function invalid(string $path, array $errors): self
    {
        return new self(
            sprintf('Ruleset file is invalid: %s (%d error(s): %s)', $path, count($errors), implode('; ', $errors)),
            $errors,
        );
    }

    public static function invalidVersion(string $version): self
    {
        return new self("Invalid ruleset version: {$version}");
    }

    public static function unknownRule(string $version, string $id): self
    {
        return new self("Ruleset {$version} has no rule {$id}");
    }

    public static function unknownProvision(string $version, string $key): self
    {
        return new self("Ruleset {$version} has no provision {$key}");
    }

    public static function unknownSource(string $version, string $key): self
    {
        return new self("Ruleset {$version} has no source {$key}");
    }

    public static function unknownOverride(string $version, string $key): self
    {
        return new self("Ruleset {$version} has no override {$key}");
    }
}
