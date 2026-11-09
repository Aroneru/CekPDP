<?php

declare(strict_types=1);

namespace App\Domain\Document;

use App\Support\Regex;
use SensitiveParameter;

final class TextStats
{
    public static function characters(#[SensitiveParameter] string $text): int
    {
        return mb_strlen($text);
    }

    public static function words(#[SensitiveParameter] string $text): int
    {
        return count(Regex::split('/[^\p{L}\p{N}]+/u', $text));
    }
}
