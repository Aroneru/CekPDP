<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Document\PolicyDocument;
use App\Domain\Rules\LocalizedText;
use App\Domain\Rules\Rule;
use App\Enums\DocumentLanguage;
use App\Enums\EvaluationSource;
use App\Enums\RuleStatus;

/**
 * Decides language rules (evaluator "language_check", i.e. PDP-LANG-01) from
 * deterministic language detection. Only English policies go to the provider,
 * which looks for a reference to an Indonesian version.
 */
final class LanguageCheck
{
    /**
     * The decision, or null when the provider should evaluate the rule.
     */
    public function decide(Rule $rule, PolicyDocument $document): ?RuleEvaluation
    {
        $reason = match ($document->language->language) {
            DocumentLanguage::Indonesian => new LocalizedText(
                'Deteksi bahasa otomatis: kebijakan ditulis dalam Bahasa Indonesia.',
                'Automatic language detection: the policy is written in Bahasa Indonesia.',
            ),
            DocumentLanguage::Mixed => new LocalizedText(
                'Deteksi bahasa otomatis: kebijakan memuat teks dalam Bahasa Indonesia dan bahasa Inggris. Pastikan versi Bahasa Indonesia lengkap.',
                'Automatic language detection: the policy contains text in both Bahasa Indonesia and English. Check that the Indonesian version is complete.',
            ),
            DocumentLanguage::Unknown => new LocalizedText(
                'Bahasa kebijakan tidak dapat ditentukan secara otomatis karena teksnya terlalu sedikit.',
                'The policy language could not be determined automatically because there is too little text.',
            ),
            DocumentLanguage::English => null,
        };

        if ($reason === null) {
            return null;
        }

        return new RuleEvaluation(
            ruleId: $rule->id,
            status: $document->language->language === DocumentLanguage::Unknown ? RuleStatus::NeedsHumanReview : RuleStatus::Addressed,
            reason: $reason,
            evidence: [],
            source: EvaluationSource::LanguageCheck,
        );
    }
}
