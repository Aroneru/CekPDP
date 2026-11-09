<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferenceRole: string
{
    /** The provision creates the requirement the rule checks. */
    case Basis = 'basis';

    /** The provision gives context, such as a related obligation. */
    case Related = 'related';
}
