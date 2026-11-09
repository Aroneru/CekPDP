<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why evidence verification changed a result to Needs human review.
 */
enum DowngradeReason: string
{
    /** A quote could not be found in the policy text. */
    case UnverifiedQuote = 'unverified_quote';

    /** The result says the policy covers the item but gives no quote. */
    case MissingEvidence = 'missing_evidence';
}
