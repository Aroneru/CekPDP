<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\AnalysisStage;
use App\Http\Support\AnalysisOwner;
use App\Models\Analysis;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Shows a running analysis's progress and polls until it finishes, then
 * reloads the page, which shows the results or the error.
 *
 * Every poll re-checks that the analysis belongs to this browser. Only status
 * columns are read; the encrypted policy text is never loaded here.
 */
class AnalysisProgress extends Component
{
    /** Stages shown to the visitor, in order. */
    public const STAGES = [
        AnalysisStage::Queued,
        AnalysisStage::Extracting,
        AnalysisStage::Structuring,
        AnalysisStage::Evaluating,
        AnalysisStage::Verifying,
        AnalysisStage::Scoring,
    ];

    #[Locked]
    public string $analysisId = '';

    public function mount(string $analysisId): void
    {
        $this->analysisId = $analysisId;
    }

    /**
     * Called by wire:poll.
     */
    public function check(): void
    {
        $analysis = $this->analysis();

        if ($analysis === null || $analysis->isFinished()) {
            $this->redirectRoute('analyses.show', ['analysis' => $this->analysisId]);
        }
    }

    public function render(): View
    {
        $analysis = $this->analysis();
        $current = $analysis === null ? 0 : array_search($analysis->stage, self::STAGES, true);

        return view('livewire.analysis-progress', [
            'analysis' => $analysis,
            'stages' => self::STAGES,
            'currentIndex' => $current === false ? count(self::STAGES) : $current,
        ]);
    }

    private function analysis(): ?Analysis
    {
        $ownerHash = app(AnalysisOwner::class)->existingHash();

        if ($ownerHash === null) {
            return null;
        }

        return Analysis::query()
            ->ownedBy($ownerHash)
            ->select(['id', 'owner_hash', 'status', 'stage', 'progress', 'created_at', 'started_at'])
            ->find($this->analysisId);
    }
}
