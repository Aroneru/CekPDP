<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisStage;
use App\Enums\AnalysisStatus;
use App\Enums\InputKind;
use App\Models\Analysis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = implode("\n\n", [
            'Kami mengumpulkan nama, alamat email, dan nomor telepon Anda saat Anda mendaftar.',
            'Data Pribadi digunakan untuk mengelola akun dan memproses pesanan Anda.',
            'Anda berhak mengakses, memperbaiki, dan menghapus Data Pribadi Anda.',
            fake()->sentence(),
        ]);

        return [
            'owner_hash' => hash('sha256', Str::random(40)),
            'status' => AnalysisStatus::Queued,
            'stage' => AnalysisStage::Queued,
            'progress' => 0,
            'input_kind' => InputKind::Text,
            'input_meta' => ['char_count' => mb_strlen($text), 'word_count' => str_word_count($text)],
            'context' => [
                'consent_basis' => 'unsure',
                'specific_data' => 'unsure',
                'children' => 'unsure',
                'international_transfer' => 'unsure',
                'automated_decisions' => 'unsure',
            ],
            'locale' => 'id',
            'ruleset_id' => 'uu-pdp-27-2022',
            'ruleset_version' => '1.0.0',
            'provider' => 'mock',
            'source_text' => $text,
            'text_sha256' => hash('sha256', $text),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => AnalysisStatus::Completed,
            'stage' => AnalysisStage::Done,
            'progress' => 100,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function failed(string $errorCode = 'analysis_failed'): static
    {
        return $this->state(fn (): array => [
            'status' => AnalysisStatus::Failed,
            'error_code' => $errorCode,
            'completed_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function expired(): static
    {
        return $this->completed()->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
