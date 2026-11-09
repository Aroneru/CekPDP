<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The language of a policy, as detected from its text.
 */
enum DocumentLanguage: string
{
    case Indonesian = 'id';
    case English = 'en';

    /** Substantial text in both languages, such as a bilingual policy. */
    case Mixed = 'mixed';

    /** Too little text to tell. */
    case Unknown = 'unknown';

    public function includesIndonesian(): bool
    {
        return $this === self::Indonesian || $this === self::Mixed;
    }
}
