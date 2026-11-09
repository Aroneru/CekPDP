<?php

declare(strict_types=1);

use App\Domain\Document\LanguageProfile;
use App\Domain\Document\PolicyDocument;
use App\Domain\Document\Section;
use App\Domain\Document\Sectionizer;
use App\Domain\Extraction\TextCleaner;
use App\Enums\DocumentLanguage;

beforeEach(function (): void {
    $this->sectionizer = new Sectionizer;
});

it('starts a section at every Markdown heading, after a preamble', function (): void {
    $text = "Kebijakan ini berlaku untuk aplikasi kami.\n\n## Data yang kami kumpulkan\n\nNama dan email.\n\n## Hak Anda\n\nAnda berhak mengakses data.";

    $sections = $this->sectionizer->split($text);

    expect(array_map(static fn (Section $section): array => [$section->id, $section->heading], $sections))->toBe([
        ['S01', null],
        ['S02', 'Data yang kami kumpulkan'],
        ['S03', 'Hak Anda'],
    ])->and($sections[1]->text)->toBe("## Data yang kami kumpulkan\n\nNama dan email.");
});

it('recognises title-like lines as headings, but not sentences, labels or addresses', function (): void {
    $text = implode("\n", [
        'Data yang Kami Kumpulkan',
        'Kami mengumpulkan nama dan email.',
        '',
        '4. Hak Anda',
        'Anda berhak mengakses data.',
        '',
        'Hubungi kami:',
        'PT Contoh Abadi',
        'Jl. Contoh Raya No. 1',
        'Email: privasi@contoh.example',
        '',
        'Kami tidak menjual data',
        'kepada siapa pun.',
    ]);

    $headings = array_map(static fn (Section $section): ?string => $section->heading, $this->sectionizer->split($text));

    expect($headings)->toBe(['Data yang Kami Kumpulkan', '4. Hak Anda']);
});

it('makes each paragraph a section when there are no headings', function (): void {
    $text = "Kami mengumpulkan nama.\n\nKami memakai data untuk pesanan.\n\nAnda dapat menghubungi kami.";

    $sections = $this->sectionizer->split($text);

    expect($sections)->toHaveCount(3)
        ->and($sections[2]->id)->toBe('S03')
        ->and($sections[2]->heading)->toBeNull()
        ->and($sections[2]->text)->toBe('Anda dapat menghubungi kami.');
});

it('splits long sections into continuations with the same heading', function (): void {
    $paragraph = str_repeat('Kami memproses data pribadi sesuai tujuan yang dijelaskan. ', 30);
    $text = "## Pemrosesan\n\n".implode("\n\n", array_fill(0, 8, trim($paragraph)));

    $sections = $this->sectionizer->split($text);

    expect(count($sections))->toBeGreaterThan(1);

    foreach ($sections as $index => $section) {
        expect($section->heading)->toBe('Pemrosesan')
            ->and($section->continued)->toBe($index > 0)
            ->and($section->end - $section->start)->toBeLessThanOrEqual(Sectionizer::MAX_SECTION_BYTES);
    }
});

it('gives the same IDs for the same text', function (): void {
    $text = fixtureText('id-01-belanja-lengkap');

    expect($this->sectionizer->split($text))->toEqual($this->sectionizer->split($text));
});

it('covers every fixture exactly, with offsets that point back into the text', function (string $slug): void {
    $text = (new TextCleaner)->clean(fixtureText($slug));
    $sections = $this->sectionizer->split($text);
    $previousEnd = 0;

    foreach ($sections as $index => $section) {
        expect($section->id)->toBe(sprintf('S%02d', $index + 1))
            ->and(substr($text, $section->start, $section->end - $section->start))->toBe($section->text)
            ->and($section->start)->toBeGreaterThanOrEqual($previousEnd)
            // Only whitespace lies between sections.
            ->and(trim(substr($text, $previousEnd, $section->start - $previousEnd)))->toBe('');

        $previousEnd = $section->end;
    }

    expect(trim(substr($text, $previousEnd)))->toBe('');
})->with(fixtureSlugs());

it('restores sections from their stored form', function (): void {
    $text = (new TextCleaner)->clean(fixtureText('en-02-saas-comprehensive'));
    $sections = $this->sectionizer->split($text);
    $language = new LanguageProfile(DocumentLanguage::English, 0, 10);

    $restored = PolicyDocument::restore($text, array_map(static fn (Section $section): array => $section->toArray(), $sections), $language);

    expect($restored->sections)->toEqual($sections)
        ->and($restored->section('S03')?->heading)->toBe('Personal data we collect')
        ->and($restored->sectionAt($sections[2]->start + 5)?->id)->toBe('S03');
});

it('refuses stored sections that do not fit the text', function (): void {
    PolicyDocument::restore('Pendek.', [['id' => 'S01', 'heading' => null, 'start' => 0, 'end' => 500, 'continued' => false]], new LanguageProfile(DocumentLanguage::Unknown, 0, 0));
})->throws(InvalidArgumentException::class);
