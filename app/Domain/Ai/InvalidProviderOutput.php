<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use RuntimeException;

/**
 * The provider's output does not match the response schema or the request.
 * The listed errors describe structure only, never policy text.
 */
final class InvalidProviderOutput extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        parent::__construct(sprintf('The provider returned output in an unexpected format (%d problem(s)).', count($errors)));
    }
}
