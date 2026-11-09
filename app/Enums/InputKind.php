<?php

declare(strict_types=1);

namespace App\Enums;

enum InputKind: string
{
    case Text = 'text';
    case Pdf = 'pdf';
    case Docx = 'docx';
}
