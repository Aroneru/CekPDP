<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use SensitiveParameter;

/**
 * Thin wrappers around preg_* that throw instead of returning null or false, so
 * callers work with plain strings and booleans. Error messages never include
 * the subject, which may be policy text.
 */
final class Regex
{
    public static function replace(string $pattern, string $replacement, #[SensitiveParameter] string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);

        if ($result === null) {
            throw self::failure();
        }

        return $result;
    }

    public static function matches(string $pattern, #[SensitiveParameter] string $subject): bool
    {
        $result = preg_match($pattern, $subject);

        if ($result === false) {
            throw self::failure();
        }

        return $result === 1;
    }

    /**
     * The first match and its byte offset, or null.
     *
     * @return array{0: string, 1: int}|null
     */
    public static function first(string $pattern, #[SensitiveParameter] string $subject, int $offset = 0): ?array
    {
        $result = preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset);

        if ($result === false) {
            throw self::failure();
        }

        return $result === 1 ? [$matches[0][0], $matches[0][1]] : null;
    }

    /**
     * Every match with its byte offset.
     *
     * @return list<array{0: string, 1: int}>
     */
    public static function all(string $pattern, #[SensitiveParameter] string $subject): array
    {
        $result = preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);

        if ($result === false) {
            throw self::failure();
        }

        $found = [];

        foreach ($matches[0] as [$text, $offset]) {
            $found[] = [$text, $offset];
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function split(string $pattern, #[SensitiveParameter] string $subject): array
    {
        $result = preg_split($pattern, $subject, -1, PREG_SPLIT_NO_EMPTY);

        if ($result === false) {
            throw self::failure();
        }

        return $result;
    }

    private static function failure(): RuntimeException
    {
        return new RuntimeException('Regular expression failed: '.preg_last_error_msg());
    }
}
