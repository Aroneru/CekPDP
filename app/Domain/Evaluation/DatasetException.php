<?php

declare(strict_types=1);

namespace App\Domain\Evaluation;

use RuntimeException;

final class DatasetException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(sprintf('Evaluation dataset is invalid (%d error(s)): %s', count($errors), implode('; ', $errors)));
    }
}
