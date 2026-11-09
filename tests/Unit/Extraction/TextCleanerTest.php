<?php

declare(strict_types=1);

use App\Domain\Extraction\TextCleaner;

beforeEach(function (): void {
    $this->cleaner = new TextCleaner;
});

it('normalises line endings', function (): void {
    expect($this->cleaner->clean("Satu.\r\nDua.\rTiga."))->toBe("Satu.\nDua.\nTiga.");
});

it('removes invisible characters and turns special spaces into plain spaces', function (): void {
    $text = "\u{FEFF}Data\u{00A0}Pribadi\u{200B} dan\tdata\u{00AD}base\u{0007} kami.";

    expect($this->cleaner->clean($text))->toBe('Data Pribadi dan database kami.');
});

it('collapses spacing and blank lines but keeps paragraph breaks', function (): void {
    $text = "  Paragraf   pertama.  \n\n\n\n   Paragraf kedua.   ";

    expect($this->cleaner->clean($text))->toBe("Paragraf pertama.\n\nParagraf kedua.");
});

it('normalises Unicode to NFC', function (): void {
    expect($this->cleaner->clean("Caf\u{0065}\u{0301} kami."))->toBe("Caf\u{00E9} kami.");
});

it('rejoins lines that were hard-wrapped in the middle of a sentence', function (): void {
    $text = "Kami mengumpulkan nama lengkap, alamat email, dan nomor telepon\nAnda ketika Anda mendaftar sebagai pengguna layanan kami dan\nmenggunakan aplikasi kami.";

    expect($this->cleaner->clean($text))->toBe("Kami mengumpulkan nama lengkap, alamat email, dan nomor telepon\nAnda ketika Anda mendaftar sebagai pengguna layanan kami dan menggunakan aplikasi kami.");
});

it('rejoins a word hyphenated across lines without adding a space', function (): void {
    $text = "Data yang kami kumpulkan dari pengguna dibagikan kepada pihak-\npihak yang membantu kami.";

    expect($this->cleaner->clean($text))->toBe('Data yang kami kumpulkan dari pengguna dibagikan kepada pihak-pihak yang membantu kami.');
});

it('keeps lists, headings, short lines and finished sentences on their own lines', function (string $text): void {
    expect($this->cleaner->clean($text))->toBe($text);
})->with([
    'list items' => "Kami mengumpulkan data berikut untuk menyediakan layanan:\n- nama lengkap dan alamat email\n- nomor telepon",
    'heading' => "## Data yang kami kumpulkan dan cara kami menggunakannya\nkami mengumpulkan nama.",
    'address lines' => "PT Contoh Abadi\nJl. Contoh Raya No. 1\nJakarta",
    'finished sentence' => "Kami menyimpan data selama lima tahun setelah akun ditutup.\nsetelah itu data dihapus.",
]);

it('scrubs invalid UTF-8 instead of failing', function (): void {
    expect($this->cleaner->clean("Data \xC3\x28 kami."))->toBeString()->toContain('Data');
});

it('never drops or changes words in the evaluation fixtures', function (string $slug): void {
    $text = fixtureText($slug);
    $words = static fn (string $value): array => preg_split('/\s+/u', trim($value), flags: PREG_SPLIT_NO_EMPTY) ?: [];

    expect($words($this->cleaner->clean($text)))->toBe($words($text));
})->with(fixtureSlugs());
