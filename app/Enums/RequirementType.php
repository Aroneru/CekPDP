<?php

declare(strict_types=1);

namespace App\Enums;

enum RequirementType: string
{
    /** UU 27/2022 expressly requires the information to be conveyed, or grants the right being disclosed. */
    case Legal = 'legal';

    /** Recommended for clarity or to show how a related obligation is met; not an express disclosure duty. */
    case BestPractice = 'best_practice';
}
