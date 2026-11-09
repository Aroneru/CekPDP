<?php

declare(strict_types=1);

namespace App\Enums;

enum ContextAnswer: string
{
    case Yes = 'yes';
    case No = 'no';
    case Unsure = 'unsure';
}
