<?php

declare(strict_types=1);

use App\Domain\Document\LanguageDetector;
use App\Enums\DocumentLanguage;

beforeEach(function (): void {
    $this->detector = new LanguageDetector;
});

it('detects the language of every evaluation fixture', function (string $slug): void {
    $profile = $this->detector->detect(fixtureText($slug));

    expect($profile->language->value)->toBe(fixtureExpectations($slug)['language']);
})->with(fixtureSlugs());

it('classifies Indonesian, English and bilingual text', function (string $text, DocumentLanguage $expected): void {
    expect($this->detector->detect($text)->language)->toBe($expected);
})->with([
    'Indonesian' => ['Kami mengumpulkan data pribadi Anda untuk memproses pesanan dan kami tidak menjualnya kepada pihak lain.', DocumentLanguage::Indonesian],
    'English' => ['We collect your personal data to process your orders and we do not sell it to anyone.', DocumentLanguage::English],
    'bilingual' => ['Kami mengumpulkan data pribadi Anda untuk memproses pesanan. We collect your personal data to process your orders.', DocumentLanguage::Mixed],
]);

it('says unknown when there is too little text to tell', function (): void {
    $profile = $this->detector->detect('PT Contoh Abadi, Jakarta. privasi@contoh.example');

    expect($profile->language)->toBe(DocumentLanguage::Unknown)
        ->and($profile->indonesianShare())->toBeNull();
});

it('keeps the evidence behind its decision', function (): void {
    $profile = $this->detector->detect('Kami dan Anda. We and you.');

    expect($profile->toArray())->toBe([
        'language' => 'mixed',
        'indonesian_words' => 3,
        'english_words' => 3,
    ]);
});
