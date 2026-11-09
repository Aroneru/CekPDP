<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Rules\ContextQuestion;
use App\Enums\ContextAnswer;

final readonly class ReportContextAnswer
{
    public function __construct(
        public ContextQuestion $question,
        public ContextAnswer $answer,
    ) {}
}
