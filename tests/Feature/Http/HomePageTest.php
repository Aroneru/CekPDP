<?php

declare(strict_types=1);

it('shows the form in Bahasa Indonesia by default', function (): void {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('<html lang="id">', false)
        ->assertSee('Periksa kebijakan privasi terhadap UU PDP')
        ->assertSee('Alat ini memberikan tinjauan awal otomatis dan bukan nasihat hukum.')
        ->assertSee('Mode offline aktif')
        ->assertSee('dihapus otomatis 30 menit setelah analisis selesai')
        ->assertSee('Jangan kirim informasi rahasia')
        ->assertSee('name="_token"', false);

    expect($response->getContent())
        ->toMatch('/<textarea[^>]*name="text"[^>]*required/s')
        ->toMatch('/<input[^>]*name="acknowledge"[^>]*required/s');
});

it('asks every context question from the ruleset, defaulting to "Not sure"', function (): void {
    $content = (string) $this->get('/')->getContent();

    foreach (activeRuleset()->contexts as $key => $question) {
        expect($content)
            ->toContain(e($question->question->id))
            ->toMatch('/name="context\['.$key.'\]"\s+value="unsure"[^>]*\bchecked\b/');
    }
});

it('loads a sample policy with its context answers', function (): void {
    $content = (string) $this->get('/?sample=id-09-umkm-minim')->assertOk()->getContent();

    expect($content)
        ->toContain('Toko Roti Bu Sari menghargai privasi pelanggan.')
        ->toContain('Contoh dimuat')
        ->toMatch('/name="context\[consent_basis\]"\s+value="yes"[^>]*\bchecked\b/')
        ->toMatch('/name="context\[children\]"\s+value="no"[^>]*\bchecked\b/');
});

it('ignores samples that are not on the list', function (string $sample): void {
    $content = (string) $this->get('/?sample='.urlencode($sample))->assertOk()->getContent();

    expect($content)->not->toContain('Contoh dimuat')
        ->and($content)->toMatch('/<textarea[^>]*>\s*<\/textarea>/s');
})->with(['en-12-injection', '../../.env', 'nope']);

it('switches the interface to English and back', function (): void {
    $this->post('/locale', ['locale' => 'en'])->assertRedirect();

    $this->get('/')
        ->assertSee('<html lang="en">', false)
        ->assertSee('Check a privacy policy against the UU PDP')
        ->assertSee('This tool gives an automated initial review and is not legal advice. Consult a qualified professional before making compliance decisions.');

    $this->post('/locale', ['locale' => 'fr']);
    $this->get('/')->assertSee('<html lang="en">', false);

    $this->post('/locale', ['locale' => 'id']);
    $this->get('/')->assertSee('<html lang="id">', false);
});
