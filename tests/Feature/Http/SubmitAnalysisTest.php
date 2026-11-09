<?php

declare(strict_types=1);

use App\Domain\Extraction\TextCleaner;
use App\Enums\AnalysisStatus;
use App\Jobs\AnalyzePolicyJob;
use App\Models\Analysis;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();

    $this->form = static fn (array $overrides = []): array => array_filter([
        'text' => fixtureText('id-09-umkm-minim'),
        'acknowledge' => '1',
        'context' => [
            'consent_basis' => 'yes',
            'specific_data' => 'no',
            'children' => 'no',
            'international_transfer' => 'no',
            'automated_decisions' => 'no',
        ],
        ...$overrides,
    ], static fn (mixed $value): bool => $value !== null);
});

it('stores a valid submission encrypted and queues it by ID only', function (): void {
    $response = $this->post('/analyses', ($this->form)());
    $analysis = Analysis::query()->sole();

    $response->assertRedirect(route('analyses.show', $analysis->id));

    expect($analysis->status)->toBe(AnalysisStatus::Queued)
        ->and($analysis->source_text)->toBe((new TextCleaner)->clean(fixtureText('id-09-umkm-minim')))
        ->and($analysis->context)->toBe(($this->form)()['context'])
        ->and($analysis->provider)->toBe('mock')
        ->and($analysis->ruleset_version)->toBe('1.0.0')
        ->and($analysis->expires_at?->isFuture())->toBeTrue()
        ->and((string) DB::table('analyses')->value('source_text'))->not->toContain('Toko Roti');

    Queue::assertPushed(AnalyzePolicyJob::class, static fn (AnalyzePolicyJob $job): bool => $job->analysisId === $analysis->id
        && ! str_contains(serialize($job), 'Toko Roti'));
});

it('shows the form again, with the text, when the notice was not acknowledged', function (): void {
    $response = $this->post('/analyses', ($this->form)(['acknowledge' => null]));

    $response->assertStatus(422)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('Centang pernyataan bahwa Anda sudah membaca pemberitahuan')
        ->assertSee('Toko Roti Bu Sari menghargai privasi pelanggan.');

    // The text is echoed in the page only, never put in the (cookie) session.
    expect(serialize(session()->all()))->not->toContain('Toko Roti')
        ->and(Analysis::query()->count())->toBe(0);

    Queue::assertNothingPushed();
});

it('reports the real size of text that is too short or too long, without cutting it', function (string $text, int $max, string $message): void {
    config(['cekpdp.limits.max_input_characters' => $max]);

    $this->post('/analyses', ($this->form)(['text' => $text]))
        ->assertStatus(422)
        ->assertSee($message);

    expect(Analysis::query()->count())->toBe(0);
})->with([
    'too short' => ['Kebijakan singkat.', 300000, 'Teks terlalu pendek: 18 karakter. Tempel kebijakan lengkap, minimal 100 karakter.'],
    'too long' => [str_repeat('Kami melindungi data pribadi Anda. ', 40), 1000, 'Teks terlalu panjang: 1.399 karakter, sedangkan batasnya 1.000 karakter.'],
]);

it('requires text', function (): void {
    $this->post('/analyses', ($this->form)(['text' => null]))
        ->assertStatus(422)
        ->assertSee('Tempel teks kebijakan privasi terlebih dahulu.');
});

it('rejects unknown context questions and answers', function (array $context): void {
    $this->post('/analyses', ($this->form)(['context' => $context]))
        ->assertStatus(422);

    expect(Analysis::query()->count())->toBe(0);
})->with([
    'unknown answer' => [['consent_basis' => 'maybe']],
    'unknown question' => [['favourite_colour' => 'yes']],
]);

it('limits accepted analyses per hour, without counting failed attempts', function (): void {
    config(['cekpdp.limits.analyses_per_hour' => 2]);

    $this->post('/analyses', ($this->form)(['acknowledge' => null]))->assertStatus(422);
    $this->post('/analyses', ($this->form)())->assertRedirect();
    $this->post('/analyses', ($this->form)())->assertRedirect();

    $this->post('/analyses', ($this->form)())
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertSee('Anda sudah menjalankan 2 analisis dalam satu jam terakhir.')
        ->assertSee('Toko Roti Bu Sari menghargai privasi pelanggan.');

    expect(Analysis::query()->count())->toBe(2);
});

it('deletes expired analyses when a new one is submitted', function (): void {
    $expired = Analysis::factory()->expired()->create();

    $this->post('/analyses', ($this->form)())->assertRedirect();

    expect(Analysis::query()->find($expired->id))->toBeNull()
        ->and(Analysis::query()->count())->toBe(1);
});

it('rejects submissions without a valid CSRF token', function (): void {
    // CSRF checks are skipped in tests by default; turn them back on.
    $this->app->singleton(PreventRequestForgery::class, static fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });

    $this->post('/analyses', ($this->form)())->assertStatus(419);

    expect(Analysis::query()->count())->toBe(0);
});
