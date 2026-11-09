<?php

declare(strict_types=1);

namespace App\Enums;

enum RuleReviewStatus: string
{
    /** Part of the active checklist. */
    case Active = 'active';

    /** Awaiting the owner's legal review; excluded from analyses. */
    case NeedsReview = 'needs_review';
}
