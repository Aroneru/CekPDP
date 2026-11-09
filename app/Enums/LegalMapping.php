<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How directly a legal rule follows from the text of the law.
 */
enum LegalMapping: string
{
    /** The provision's words cover the item. */
    case Direct = 'direct';

    /** The link needs a reasoning step, which is shown to users as an interpretation. */
    case Interpretive = 'interpretive';
}
