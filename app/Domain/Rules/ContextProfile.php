<?php

declare(strict_types=1);

namespace App\Domain\Rules;

use App\Enums\ContextAnswer;
use InvalidArgumentException;

/**
 * The user's answers to the ruleset's context questions, with defaults filled in.
 */
final readonly class ContextProfile
{
    /**
     * @param  array<string, ContextAnswer>  $answers
     */
    private function __construct(
        public array $answers,
    ) {}

    /**
     * @param  array<array-key, mixed>  $input  context key => answer value
     *
     * @throws InvalidArgumentException for unknown questions or answers
     */
    public static function fromInput(Ruleset $ruleset, array $input): self
    {
        $unknown = array_diff(array_keys($input), array_keys($ruleset->contexts));

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown context question(s): '.implode(', ', $unknown));
        }

        $answers = [];

        foreach ($ruleset->contexts as $key => $question) {
            $answers[$key] = self::answerFor($question, $input[$key] ?? null);
        }

        return new self($answers);
    }

    public static function defaults(Ruleset $ruleset): self
    {
        return self::fromInput($ruleset, []);
    }

    public function answer(string $key): ContextAnswer
    {
        return $this->answers[$key] ?? throw new InvalidArgumentException("Unknown context question: {$key}");
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_map(static fn (ContextAnswer $answer): string => $answer->value, $this->answers);
    }

    private static function answerFor(ContextQuestion $question, mixed $value): ContextAnswer
    {
        if ($value === null || $value === '') {
            return $question->default;
        }

        $answer = match (true) {
            $value instanceof ContextAnswer => $value,
            is_string($value) => ContextAnswer::tryFrom($value),
            default => null,
        };

        if ($answer === null || ! $question->allows($answer)) {
            throw new InvalidArgumentException("Invalid answer for context question {$question->key}");
        }

        return $answer;
    }
}
