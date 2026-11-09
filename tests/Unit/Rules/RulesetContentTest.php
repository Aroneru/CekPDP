<?php

declare(strict_types=1);

/**
 * Guards the legal content of the ruleset itself. The official copy of
 * UU 27/2022 is a scan whose OCR layer drops words; these checks make sure the
 * verified wording (docs/legal/SOURCES.md, section 1.2) can't silently regress.
 */

use App\Domain\Rules\ImplementingRegulation;
use App\Domain\Rules\RulesetRepository;
use App\Enums\RequirementType;

beforeEach(function (): void {
    $this->ruleset = app(RulesetRepository::class)->active();
});

it('keeps the verified wording where the OCR layer was wrong', function (string $provision, string $phrase): void {
    expect($this->ruleset->provision($provision)->text->id)->toContain($phrase);
})->with([
    'Pasal 4(2)(f)' => ['p4-2', 'f. data keuangan pribadi'],
    'Pasal 11' => ['p11', 'secara proporsional sesuai dengan tujuan'],
    'Pasal 13(2)' => ['p13-2', 'berhak menggunakan dan mengirimkan Data Pribadi'],
    'Pasal 16(2)(a)' => ['p16-2', 'a. pengumpulan Data Pribadi dilakukan secara terbatas'],
    'Pasal 16(2)(e)' => ['p16-2', 'pengubahan yang tidak sah, penyalahgunaan, perusakan'],
    'Pasal 21(1)(e)' => ['p21-1', 'e. rincian mengenai Informasi yang dikumpulkan;'],
    'Pasal 21(1)(g)' => ['p21-1', 'g. hak Subjek Data Pribadi.'],
    'Pasal 25(1)' => ['p25', 'Pemrosesan Data Pribadi anak diselenggarakan secara khusus.'],
    'Pasal 46(1)' => ['p46', 'wajib menyampaikan pemberitahuan secara tertulis paling lambat 3 x 24'],
    'Pasal 48(1)' => ['p48', 'wajib menyampaikan pemberitahuan pengalihan Data Pribadi'],
]);

it('records how MK Decision 151/PUU-XXII/2024 changed Pasal 53(1)', function (): void {
    $decision = $this->ruleset->instrument->courtDecisions[0];

    expect($decision->reference)->toBe('Putusan Mahkamah Konstitusi Nomor 151/PUU-XXII/2024')
        ->and($decision->pronouncedOn->toDateString())->toBe('2025-07-30')
        ->and($decision->affectsProvision)->toBe('p53-1')
        ->and($this->ruleset->provision('p53-1')->modifiedBy)->toBe($decision->reference);
});

it('keeps implementing regulations that are not in force out of the rules', function (): void {
    foreach ($this->ruleset->instrument->implementingRegulations as $regulation) {
        expect($regulation->status)->not->toBe(ImplementingRegulation::STATUS_IN_FORCE)
            ->and($regulation->usedByRules)->toBeFalse();
    }
});

it('grounds every legal requirement in a provision that imposes an information duty', function (): void {
    // Pasal 5 (right to information), Pasal 16(2)(f) (notifying purposes and
    // processing activities), Pasal 21 (information for consent-based processing)
    // and Pasal 27 with its Elucidation (transparency). See PLAN.md section 3.1.
    $informationDuties = ['p5', 'p16-2', 'p21-1', 'p21-2', 'p27', 'pj-p27'];

    foreach ($this->ruleset->activeRules() as $rule) {
        if ($rule->requirementType !== RequirementType::Legal) {
            continue;
        }

        $basis = array_map(fn ($reference) => $reference->provisionKey, $rule->basisReferences());

        expect(array_intersect($basis, $informationDuties))
            ->not->toBeEmpty("{$rule->id} has no basis that imposes an information duty");
    }
});

it('shows the reasoning for every interpretive rule in both languages', function (): void {
    foreach ($this->ruleset->activeRules() as $rule) {
        if ($rule->isInterpretive()) {
            expect($rule->interpretationNote?->id)->not->toBeEmpty()
                ->and($rule->interpretationNote?->en)->not->toBeEmpty();
        }
    }
});
