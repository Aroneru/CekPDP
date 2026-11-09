<?php

declare(strict_types=1);

use App\Models\Analysis;
use App\Models\RuleResult;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('deletes expired analyses with their results and keeps the rest', function (): void {
    $expired = RuleResult::factory()->for(Analysis::factory()->expired())->create()->analysis;
    $current = RuleResult::factory()->for(Analysis::factory()->completed())->create()->analysis;
    $pending = Analysis::factory()->create(['expires_at' => now()->addHour()]);

    $this->artisan('cekpdp:purge-expired')
        ->expectsOutput('Deleted 1 expired analysis.')
        ->assertSuccessful();

    expect(Analysis::query()->pluck('id')->sort()->values()->all())->toBe(collect([$current->id, $pending->id])->sort()->values()->all())
        ->and(RuleResult::query()->where('analysis_id', $expired->id)->count())->toBe(0)
        ->and(RuleResult::query()->where('analysis_id', $current->id)->count())->toBe(1);
});

it('deletes analyses that never finished once their pending window ends', function (): void {
    Analysis::factory()->create(['expires_at' => now()->subMinute()]);

    $this->artisan('cekpdp:purge-expired')->expectsOutput('Deleted 1 expired analysis.');

    expect(Analysis::query()->count())->toBe(0);
});

it('runs every minute', function (): void {
    $events = collect(app(Schedule::class)->events())
        ->filter(static fn (Event $event): bool => str_contains((string) $event->command, 'cekpdp:purge-expired'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('* * * * *');
});
