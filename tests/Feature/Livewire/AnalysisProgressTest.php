<?php

declare(strict_types=1);

use App\Enums\AnalysisStage;
use App\Enums\AnalysisStatus;
use App\Livewire\AnalysisProgress;
use App\Models\Analysis;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function (): void {
    $token = str_repeat('a', 64);
    $this->withSession(['cekpdp.owner' => $token]);

    $this->analysis = Analysis::factory()->create([
        'owner_hash' => hash('sha256', $token),
        'status' => AnalysisStatus::Processing,
        'stage' => AnalysisStage::Evaluating,
        'progress' => 47,
    ]);
});

it('shows the current stage and progress', function (): void {
    Livewire::test(AnalysisProgress::class, ['analysisId' => $this->analysis->id])
        ->assertSee('Tahap saat ini: Menilai setiap butir.')
        ->assertSeeHtml('value="47"')
        ->assertSeeHtml('aria-current="step"')
        ->assertSee('(selesai)')
        ->assertSee('(belum dimulai)');
});

it('reloads the page when the analysis finishes', function (AnalysisStatus $status): void {
    $component = Livewire::test(AnalysisProgress::class, ['analysisId' => $this->analysis->id]);

    $this->analysis->forceFill(['status' => $status])->save();

    $component->call('check')->assertRedirect(route('analyses.show', $this->analysis->id));
})->with([AnalysisStatus::Completed, AnalysisStatus::Failed]);

it('keeps polling while the analysis runs', function (): void {
    Livewire::test(AnalysisProgress::class, ['analysisId' => $this->analysis->id])
        ->call('check')
        ->assertNoRedirect();
});

it('shows nothing about analyses that belong to someone else', function (): void {
    $theirs = Analysis::factory()->create(['progress' => 63]);

    Livewire::test(AnalysisProgress::class, ['analysisId' => $theirs->id])
        ->assertDontSeeHtml('value="63"')
        ->call('check')
        ->assertRedirect(route('analyses.show', $theirs->id));
});

it('does not let the browser change which analysis it watches', function (): void {
    Livewire::test(AnalysisProgress::class, ['analysisId' => $this->analysis->id])
        ->set('analysisId', 'something-else');
})->throws(CannotUpdateLockedPropertyException::class);
