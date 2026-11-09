@php
    /** @var \App\Domain\Reporting\Report $report */
    $score = $report->score;
    $legal = $score->legal;
    $statuses = ['addressed', 'partially_addressed', 'not_found', 'needs_human_review'];
    $locale = app()->getLocale();
@endphp

{{-- The figure is set in neutral ink on purpose: it measures disclosure, it is not a verdict. --}}
<section aria-labelledby="score-heading" class="grid gap-10 border-t-2 border-ink pt-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16">
    <div>
        <h2 id="score-heading" class="eyebrow">{{ __('analysis.score.title') }}</h2>

        @if ($score->isWithheld())
            <p class="mt-4 font-serif text-5xl leading-none font-medium">{{ __('analysis.score.withheld_label') }}</p>
            <p class="mt-5 max-w-md border-l-4 border-review pl-4 text-ink-soft">
                {{ __('analysis.score.withheld.'.$score->withheld->value, [
                    'count' => $legal->needsReview,
                    'total' => $legal->total(),
                    'share' => $score->reviewSharePercent(),
                    'threshold' => $score->reviewThresholdPercent,
                ]) }}
            </p>
        @else
            <p class="mt-2 font-serif text-[6.5rem] leading-none font-medium tracking-tight sm:text-[8rem]">{{ $score->percent }}<span class="text-[0.5em] text-muted">%</span></p>
            <p class="mt-4 inline-block border border-rule-strong bg-sheet px-3 py-1.5 font-mono text-sm">
                {{ __('analysis.score.arithmetic', [
                    'addressed' => $legal->addressed,
                    'partial' => $legal->partiallyAddressed,
                    'evaluated' => $legal->evaluated(),
                    'percent' => $score->percent,
                ]) }}
            </p>
            @if ($legal->needsReview > 0)
                <p class="mt-2 text-sm text-review">{{ __('analysis.score.excluded', ['count' => $legal->needsReview]) }}</p>
            @endif
        @endif

        <p class="mt-6 max-w-md font-serif text-lg leading-snug text-ink-soft italic">{{ __('analysis.score.explanation') }}</p>
        <p class="mt-3 max-w-md text-xs leading-relaxed text-muted">{{ __('analysis.score.formula') }}</p>
    </div>

    <div class="space-y-8">
        @foreach ([
            ['items' => $report->legal, 'counts' => $legal, 'label' => __('analysis.results.legal_heading'), 'note' => __('analysis.results.counted')],
            ['items' => $report->bestPractices, 'counts' => $score->bestPractice, 'label' => __('analysis.results.best_heading'), 'note' => __('analysis.results.not_counted')],
        ] as $group)
            <div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-rule pb-2">
                    <h3 class="font-serif text-xl font-medium">{{ $group['label'] }} <span class="font-mono text-sm text-muted">{{ count($group['items']) }}</span></h3>
                    <p class="font-mono text-xs text-muted uppercase">{{ $group['note'] }}</p>
                </div>

                <ul class="mt-4 flex flex-wrap gap-1" aria-label="{{ __('analysis.results.strip', ['group' => $group['label']]) }}">
                    @foreach ($group['items'] as $item)
                        @php($key = $item->status?->value ?? 'not_applicable')
                        <li>
                            <a href="#rule-{{ $item->rule->id }}" class="block p-0.5" title="{{ $item->rule->id }} · {{ $item->rule->title->in($locale) }} · {{ __('analysis.status.'.$key) }}">
                                <span class="cell cell-{{ $key }}" aria-hidden="true"></span>
                                <span class="sr-only">{{ $item->rule->title->in($locale) }}: {{ __('analysis.status.'.$key) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <ul class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                    @foreach ($statuses as $status)
                        <li class="flex items-center gap-2">
                            <x-status-icon :status="$status" :class="'size-4 shrink-0 status-'.$status" />
                            <span class="font-mono font-medium tabular-nums">{{ $group['counts']->toArray()[$status] }}</span>
                            <span class="text-muted">{{ __('analysis.status.'.$status) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
