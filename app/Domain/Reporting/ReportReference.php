<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Rules\LocalizedText;
use App\Domain\Rules\Provision;
use App\Domain\Rules\Source;
use App\Enums\ReferenceRole;

/**
 * A rule's legal reference, ready to show: the verified provision, a link to
 * the page of the official copy, and an optional link to a third-party reader.
 */
final readonly class ReportReference
{
    public function __construct(
        public Provision $provision,
        public ReferenceRole $role,
        public ?LocalizedText $pinpoint,
        public Source $source,
        public ?string $officialUrl,
        public ?string $readerUrl,
    ) {}

    /**
     * The first page of the provision in the official PDF.
     */
    public function page(): int
    {
        return (int) explode('-', $this->provision->pdfPage)[0];
    }
}
