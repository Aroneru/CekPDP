<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

/**
 * An original, fictional policy from the evaluation dataset, offered on the
 * home page so people can try CekPDP without pasting their own text.
 */
final readonly class SamplePolicy
{
    /**
     * @param  array<string, string>  $context  suggested context answers
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $language,
        public array $context,
        public string $text,
    ) {}
}
