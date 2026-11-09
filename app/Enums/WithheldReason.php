<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why no Policy Coverage figure is shown.
 */
enum WithheldReason: string
{
    /** More than the threshold share of legal requirements need human review. */
    case TooManyReviews = 'too_many_reviews';

    /** No legal requirement applies, so there is nothing to measure. */
    case NoLegalRequirements = 'no_legal_requirements';
}
