<?php

declare(strict_types=1);

it('validates every ruleset version', function (): void {
    $this->artisan('cekpdp:ruleset:validate')
        ->expectsOutputToContain('Ruleset uu-pdp-27-2022 1.0.0 is valid. Status: active; last reviewed 2026-11-09; used for new analyses.')
        ->assertSuccessful();
});

it('fails for a version that does not exist', function (): void {
    $this->artisan('cekpdp:ruleset:validate', ['version' => '9.9.9'])
        ->expectsOutputToContain('Ruleset file not found')
        ->assertFailed();
});
