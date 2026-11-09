<?php

declare(strict_types=1);

namespace App\Domain\Document;

use App\Enums\DocumentLanguage;
use App\Support\Regex;

/**
 * Deterministic language detection for Indonesian and English policies.
 *
 * It counts common function words of each language, which almost never appear
 * in the other one. A text needs at least MINIMUM_WORDS of them to be
 * classified; when neither language reaches DOMINANT_SHARE the text is "mixed"
 * (typically a bilingual policy).
 */
final class LanguageDetector
{
    public const MINIMUM_WORDS = 5;

    public const DOMINANT_SHARE = 0.8;

    private const INDONESIAN = [
        'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'kami', 'anda', 'ini', 'itu', 'atau',
        'tidak', 'akan', 'dalam', 'pada', 'oleh', 'sebagai', 'adalah', 'dapat', 'kepada', 'tersebut',
        'jika', 'apabila', 'karena', 'juga', 'telah', 'sudah', 'bahwa', 'serta', 'setiap', 'atas',
        'bagi', 'hanya', 'secara', 'selama', 'hingga', 'sampai', 'agar', 'maupun', 'kecuali',
        'melalui', 'terhadap', 'saat', 'ketika', 'kita', 'mereka', 'belum', 'lebih', 'harus',
        'perlu', 'bisa', 'masih', 'tentang', 'antara', 'sesuai', 'berdasarkan', 'termasuk',
        'lainnya', 'beberapa', 'semua', 'seluruh', 'setelah', 'sebelum', 'sejak', 'namun',
        'tetapi', 'yaitu', 'yakni', 'merupakan', 'tanpa', 'guna', 'demi',
    ];

    private const ENGLISH = [
        'the', 'and', 'of', 'to', 'in', 'for', 'with', 'we', 'you', 'your', 'our', 'this', 'that',
        'is', 'are', 'be', 'by', 'or', 'not', 'will', 'may', 'on', 'as', 'from', 'at', 'it', 'an',
        'if', 'which', 'who', 'when', 'have', 'has', 'can', 'these', 'those', 'any', 'such',
        'about', 'us', 'their', 'they', 'been', 'was', 'were', 'would', 'should', 'must', 'also',
        'how', 'what', 'where', 'other', 'only', 'more', 'into', 'under', 'within', 'without',
        'because', 'before', 'after', 'until', 'while', 'than', 'its', 'them', 'there', 'does',
    ];

    /** @var array<string, true>|null */
    private ?array $indonesian = null;

    /** @var array<string, true>|null */
    private ?array $english = null;

    public function detect(#[\SensitiveParameter] string $text): LanguageProfile
    {
        $this->indonesian ??= array_fill_keys(self::INDONESIAN, true);
        $this->english ??= array_fill_keys(self::ENGLISH, true);

        $indonesian = 0;
        $english = 0;

        foreach (Regex::split('/[^\p{L}]+/u', mb_strtolower($text)) as $word) {
            if (isset($this->indonesian[$word])) {
                $indonesian++;
            } elseif (isset($this->english[$word])) {
                $english++;
            }
        }

        return new LanguageProfile($this->classify($indonesian, $english), $indonesian, $english);
    }

    private function classify(int $indonesian, int $english): DocumentLanguage
    {
        $total = $indonesian + $english;

        if ($total < self::MINIMUM_WORDS) {
            return DocumentLanguage::Unknown;
        }

        $share = $indonesian / $total;

        return match (true) {
            $share >= self::DOMINANT_SHARE => DocumentLanguage::Indonesian,
            $share <= 1 - self::DOMINANT_SHARE => DocumentLanguage::English,
            default => DocumentLanguage::Mixed,
        };
    }
}
