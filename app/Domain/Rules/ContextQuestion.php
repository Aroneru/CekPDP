<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\ContextAnswer;

/**
 * An optional question asked before analysis that decides which rules apply.
 */
final readonly class ContextQuestion
{
    /**
     * @param  list<ContextAnswer>  $options
     */
    public function __construct(
        public string $key,
        public LocalizedText $question,
        public ?LocalizedText $help,
        public array $options,
        public ContextAnswer $default,
    ) {}

    public function allows(ContextAnswer $answer): bool
    {
        return in_array($answer, $this->options, true);
    }
}
