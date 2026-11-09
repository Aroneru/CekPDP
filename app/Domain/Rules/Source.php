<?php

declare(strict_types=1);

namespace App\Domain\Rules;

/**
 * Where legal texts come from. Only `official` sources can support a provision;
 * `secondary_reader` sources (such as Pasal.id) are convenience links.
 */
final readonly class Source
{
    public const KIND_OFFICIAL = 'official';

    public const KIND_SECONDARY_READER = 'secondary_reader';

    public function __construct(
        public string $key,
        public string $kind,
        public string $name,
        public ?string $url,
        public ?string $documentUrl,
        public ?string $sha256,
        public ?string $urlTemplate,
    ) {}

    public function isOfficial(): bool
    {
        return $this->kind === self::KIND_OFFICIAL;
    }

    /**
     * Builds an article link from the source's URL template, e.g. Pasal.id's
     * "https://pasal.id/peraturan/uu/uu-no-27-tahun-2022/pasal-{article}".
     */
    public function articleUrl(int $article): ?string
    {
        if ($this->urlTemplate === null) {
            return null;
        }

        return str_replace('{article}', (string) $article, $this->urlTemplate);
    }
}
