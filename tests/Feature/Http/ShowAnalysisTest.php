<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use App\Models\RuleResult;

beforeEach(function (): void {
    // The queue runs synchronously in tests, so a submission is analysed at once.
    $this->submit = function (string $slug = 'id-03-fintech-sedang'): Analysis {
        $this->post('/analyses', [
            'text' => fixtureText($slug),
            'acknowledge' => '1',
            'context' => fixtureExpectations($slug)['context'],
        ])->assertRedirect();

        return Analysis::query()->latest()->firstOrFail();
    };
});

it('runs a pasted policy end to end and explains the score', function (): void {
    $analysis = ($this->submit)();
    $score = $analysis->score;

    expect($analysis->status)->toBe(AnalysisStatus::Completed);

    $arithmetic = sprintf(
        '(%d + 0,5 × %d) ÷ %d = %d%%',
        $score['legal']['addressed'],
        $score['legal']['partially_addressed'],
        $score['legal']['addressed'] + $score['legal']['partially_addressed'] + $score['legal']['not_found'],
        $score['percent'],
    );

    $response = $this->get(route('analyses.show', $analysis->id));

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('Cakupan Kebijakan')
        ->assertSee($arithmetic)
        ->assertSee('Ini mengukur pengungkapan informasi, bukan kepatuhan.')
        ->assertSee('Hasil ini dibuat dalam mode offline')
        ->assertSee('Versi 1.0.0 (ditinjau 9 November 2026)')
        ->assertSee('Draf, perlu ditinjau ahli hukum')
        ->assertSee('Teks resmi (JDIH BPK, PDF hlm.')
        ->assertSee('Baca di Pasal.id (tidak resmi)')
        ->assertSee('Hapus sekarang');

    // Every active rule appears once, with not-applicable rules listed separately.
    expect(substr_count((string) $response->getContent(), '<details id="rule-'))->toBe(31)
        ->and((string) $response->getContent())->toContain('Tidak berlaku karena jawaban Anda:');
});

it('shows the results in English after switching language', function (): void {
    $analysis = ($this->submit)('en-04-gdpr-style');

    $this->post('/locale', ['locale' => 'en']);

    $this->get(route('analyses.show', $analysis->id))
        ->assertOk()
        ->assertSee('Policy Coverage')
        ->assertSee('It measures disclosure, not compliance.')
        ->assertSee('0.5 ×')
        ->assertSee('Draft, needs legal review');
});

it('hides an analysis from other browsers', function (): void {
    $analysis = ($this->submit)();

    $this->flushSession();

    $this->get(route('analyses.show', $analysis->id))
        ->assertNotFound()
        ->assertSee('Analisis tidak ditemukan')
        ->assertDontSee('Cakupan Kebijakan');
});

it('does not reveal analyses owned by someone else', function (): void {
    $theirs = Analysis::factory()->completed()->create();
    ($this->submit)();

    $this->get(route('analyses.show', $theirs->id))->assertNotFound();
});

it('shows progress while an analysis is waiting or running', function (): void {
    $analysis = ($this->submit)();
    $analysis->forceFill(['status' => AnalysisStatus::Queued])->save();

    $this->get(route('analyses.show', $analysis->id))
        ->assertOk()
        ->assertSee('Menganalisis kebijakan')
        ->assertSee('wire:poll.2s="check"', false)
        ->assertSee('<noscript><meta http-equiv="refresh" content="5"></noscript>', false);
});

it('explains a failed analysis without technical details', function (): void {
    $analysis = ($this->submit)();
    $analysis->forceFill(['status' => AnalysisStatus::Failed, 'error_code' => 'invalid_provider_output'])->save();

    $this->get(route('analyses.show', $analysis->id))
        ->assertOk()
        ->assertSee('Analisis gagal')
        ->assertSee('Tidak ada hasil sebagian yang ditampilkan.')
        ->assertSee('Hapus sekarang');
});

it('deletes an analysis and its results on request', function (): void {
    $analysis = ($this->submit)();

    $this->delete(route('analyses.destroy', $analysis->id))
        ->assertRedirect(route('home'))
        ->assertSessionHas('status', 'Analisis beserta teks kebijakannya sudah dihapus.');

    expect(Analysis::query()->count())->toBe(0)
        ->and(RuleResult::query()->count())->toBe(0);
});

it('cannot delete someone else\'s analysis', function (): void {
    $theirs = Analysis::factory()->completed()->create();
    ($this->submit)();

    $this->delete(route('analyses.destroy', $theirs->id))->assertRedirect(route('home'));

    expect(Analysis::query()->find($theirs->id))->not->toBeNull();
});

it('only accepts analysis IDs that look like ULIDs', function (): void {
    $this->get('/analyses/not-a-ulid')->assertNotFound();
});
