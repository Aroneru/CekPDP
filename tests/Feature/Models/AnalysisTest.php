<?php

declare(strict_types=1);

use App\Enums\AnalysisStatus;
use App\Enums\RuleStatus;
use App\Models\Analysis;
use App\Models\RuleResult;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

it('stores policy text and sections encrypted at rest', function (): void {
    $analysis = Analysis::factory()->create([
        'source_text' => 'Kami mengumpulkan nama dan alamat email Anda.',
        'sections' => [['id' => 'S01', 'heading' => 'Data', 'text' => 'Kami mengumpulkan nama.']],
    ]);

    $raw = DB::table('analyses')->where('id', $analysis->id)->first(['source_text', 'sections']);

    expect($raw->source_text)->not->toContain('mengumpulkan')
        ->and($raw->sections)->not->toContain('mengumpulkan')
        ->and(Crypt::decryptString($raw->source_text))->toBe('Kami mengumpulkan nama dan alamat email Anda.')
        ->and($analysis->fresh()?->sections[0]['id'])->toBe('S01');
});

it('stores reasons and evidence encrypted at rest', function (): void {
    $result = RuleResult::factory()->create(['reason_en' => 'Quoted from the policy: "Kami mengumpulkan nama."']);

    $raw = DB::table('rule_results')->where('id', $result->id)->first(['reason_en', 'evidence']);

    expect($raw->reason_en)->not->toContain('Kami mengumpulkan')
        ->and($raw->evidence)->not->toContain('Kami menggunakan')
        ->and($result->fresh()?->evidence[0]['section_id'])->toBe('S02');
});

it('casts statuses to enums', function (): void {
    $result = RuleResult::factory()->create();

    expect($result->fresh()?->status)->toBe(RuleStatus::Addressed)
        ->and($result->analysis->status)->toBe(AnalysisStatus::Completed);
});

it('deletes rule results together with their analysis', function (): void {
    $analysis = Analysis::factory()->completed()->create();
    RuleResult::factory()->count(2)->sequence(['rule_id' => 'PDP-ID-01'], ['rule_id' => 'PDP-ID-02'])->create(['analysis_id' => $analysis->id]);

    $analysis->delete();

    expect(RuleResult::query()->count())->toBe(0);
});

it('finds analyses by owner and by expiry', function (): void {
    $mine = Analysis::factory()->completed()->create(['owner_hash' => str_repeat('a', 64)]);
    $expired = Analysis::factory()->expired()->create();
    Analysis::factory()->create();

    expect(Analysis::query()->ownedBy(str_repeat('a', 64))->pluck('id')->all())->toBe([$mine->id])
        ->and(Analysis::query()->expired()->pluck('id')->all())->toBe([$expired->id]);
});

it('enforces one result per rule per analysis', function (): void {
    $analysis = Analysis::factory()->completed()->create();
    RuleResult::factory()->create(['analysis_id' => $analysis->id, 'rule_id' => 'PDP-ID-01']);

    expect(fn () => RuleResult::factory()->create(['analysis_id' => $analysis->id, 'rule_id' => 'PDP-ID-01']))
        ->toThrow(UniqueConstraintViolationException::class);
});
