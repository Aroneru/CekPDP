<?php

declare(strict_types=1);

use App\Domain\Document\SentenceSplitter;
use App\Domain\Document\TextUnit;

beforeEach(function (): void {
    $this->splitter = new SentenceSplitter;
});

it('splits sentences without breaking at abbreviations, initials or email addresses', function (): void {
    $document = policyDocument('PT Contoh Abadi berkantor di Jl. Contoh No. 5, Jakarta. Hubungi A. Wijaya di privasi@contoh.example. We use data, e.g. your name. Done!');

    $texts = array_map(static fn (TextUnit $unit): string => $unit->text, $this->splitter->split($document));

    expect($texts)->toBe([
        'PT Contoh Abadi berkantor di Jl. Contoh No. 5, Jakarta.',
        'Hubungi A. Wijaya di privasi@contoh.example.',
        'We use data, e.g. your name.',
        'Done!',
    ]);
});

it('leaves out bullets, numbering and heading marks', function (): void {
    $document = policyDocument("## 1. Data kami\n\n- nama lengkap;\n2) nomor telepon;\n(a) alamat email.");

    $texts = array_map(static fn (TextUnit $unit): string => $unit->text, $this->splitter->split($document));

    expect($texts)->toBe(['Data kami', 'nama lengkap;', 'nomor telepon;', 'alamat email.']);
});

it('links list items to the sentence that introduces them', function (): void {
    $document = policyDocument("Anda berhak:\n\n- mengakses data;\n- memperbaiki data.\n\nKami menjawab dalam 3 x 24 jam.");

    $units = $this->splitter->split($document);

    expect(array_map(static fn (TextUnit $unit): ?int => $unit->listId, $units))->toBe([1, 1, 1, null]);
});

it('produces verbatim units with correct offsets for every fixture', function (string $slug): void {
    $document = policyDocument(fixtureText($slug));

    foreach ($this->splitter->split($document) as $unit) {
        expect(substr($document->text, $unit->start, $unit->end - $unit->start))->toBe($unit->text)
            ->and($document->section($unit->sectionId)?->text)->toContain($unit->text)
            ->and(trim($unit->text))->toBe($unit->text);
    }
})->with(fixtureSlugs());
