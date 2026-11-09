<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RequirementType;
use App\Enums\RuleStatus;
use App\Models\Analysis;
use App\Models\RuleResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuleResult>
 */
class RuleResultFactory extends Factory
{
    protected $model = RuleResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_id' => Analysis::factory()->completed(),
            'rule_id' => 'PDP-PURP-01',
            'requirement_type' => RequirementType::Legal,
            'applicable' => true,
            'status' => RuleStatus::Addressed,
            'model_status' => RuleStatus::Addressed,
            'reason_id' => 'Kebijakan menyebutkan tujuan pemrosesan secara spesifik.',
            'reason_en' => 'The policy states specific purposes of processing.',
            'evidence' => [[
                'section_id' => 'S02',
                'quote' => 'Kami menggunakan Data Pribadi Anda untuk memproses pesanan.',
                'start' => 120,
                'end' => 178,
                'verified' => true,
            ]],
            'verification' => ['failed_quotes' => 0],
        ];
    }

    public function notApplicable(): static
    {
        return $this->state(fn (): array => [
            'applicable' => false,
            'status' => null,
            'model_status' => null,
            'reason_id' => null,
            'reason_en' => null,
            'evidence' => null,
            'verification' => null,
        ]);
    }
}
