@php
    /** @var \App\Domain\Reporting\Report $report */
    $locale = app()->getLocale();
    $policyLanguage = $report->language->language === \App\Enums\DocumentLanguage::English ? 'en' : 'id';
    $number = fn (int $value): string => \Illuminate\Support\Number::format($value, locale: $locale);
    $reviewItems = $report->needsReview();
    $providerLabel = \Illuminate\Support\Facades\Lang::has('analysis.results.providers.'.$report->provider) ? __('analysis.results.providers.'.$report->provider) : $report->provider;
@endphp

<x-layout :title="__('analysis.results.title')" noindex>
    {{-- Report header --}}
    <header class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
        <div>
            <p class="eyebrow">
                {{ __('analysis.results.eyebrow') }}
                @if ($report->completedAt)
                    · {{ $report->completedAt->locale($locale)->translatedFormat('j M Y, H:i T') }}
                @endif
            </p>
            <h1 class="mt-4 font-serif text-5xl leading-none font-medium tracking-tight sm:text-6xl">{{ __('analysis.results.title') }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-quiet">{{ __('ui.new_analysis') }}</a>
            <form method="POST" action="{{ route('analyses.destroy', $report->analysisId) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-quiet hover:border-rubric hover:text-rubric">
                    <svg aria-hidden="true" viewBox="0 0 16 16" class="size-4" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2.5 4h11M6 4V2.5h4V4M4 4l.75 9.5h6.5L12 4M6.75 6.5v4.5M9.25 6.5v4.5" /></svg>
                    {{ __('analysis.delete') }}
                </button>
            </form>
        </div>
    </header>

    <dl class="mt-8 grid grid-cols-2 gap-x-6 border-y border-rule py-1 text-sm md:grid-cols-4">
        @foreach ([
            __('analysis.results.details.language') => __('analysis.results.languages.'.$report->language->language->value),
            __('analysis.results.details.size') => __('analysis.results.details.size_value', ['characters' => $number($report->characters), 'words' => $number($report->words), 'sections' => $number($report->sections)]),
            __('analysis.results.details.provider') => $providerLabel,
            __('analysis.results.details.ruleset') => __('analysis.results.details.ruleset_value', ['version' => $report->ruleset->version, 'date' => $report->ruleset->lastReviewed->locale($locale)->translatedFormat('j F Y')]),
        ] as $term => $value)
            <div class="py-3">
                <dt class="eyebrow">{{ $term }}</dt>
                <dd class="mt-1">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-x-8 gap-y-3 text-sm">
        @if ($report->isOffline())
            <p class="flex max-w-2xl items-start gap-3 text-ink-soft">
                <span class="stamp shrink-0">{{ __('analysis.results.offline_stamp') }}</span>
                <span>{{ __('analysis.results.offline') }}</span>
            </p>
        @endif
        @if ($report->expiresAt)
            <p class="font-mono text-xs text-muted">{{ __('analysis.results.retention', ['time' => $report->expiresAt->diffForHumans()]) }}</p>
        @endif
    </div>

    <div class="mt-14">
        @include('partials.score-card', ['report' => $report])
    </div>

    @if ($reviewItems !== [])
        <section aria-labelledby="review-heading" class="mt-14 border-l-4 border-review bg-review-soft p-6">
            <h2 id="review-heading" class="font-serif text-2xl font-medium">{{ __('analysis.results.review_heading') }}</h2>
            <p class="mt-1 text-ink-soft">{{ __('analysis.results.review_intro') }}</p>
            <ul class="mt-3 space-y-1">
                @foreach ($reviewItems as $item)
                    <li><a href="#rule-{{ $item->rule->id }}" class="link">{{ $item->rule->title->in($locale) }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    @foreach ([
        ['id' => 'legal', 'items' => $report->legal, 'note' => __('analysis.results.counted'), 'intro' => null],
        ['id' => 'best', 'items' => $report->bestPractices, 'note' => __('analysis.results.not_counted'), 'intro' => null],
        ['id' => 'not-applicable', 'items' => $report->notApplicable, 'note' => null, 'intro' => __('analysis.results.not_applicable_intro')],
    ] as $group)
        @continue($group['id'] === 'not-applicable' && $group['items'] === [])
        <section aria-labelledby="{{ $group['id'] }}-heading" class="mt-16">
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 border-b-2 border-ink pb-3">
                <h2 id="{{ $group['id'] }}-heading" class="font-serif text-3xl font-medium">
                    {{ __('analysis.results.'.str_replace('-', '_', $group['id']).'_heading') }}
                    <span class="font-mono text-base font-normal text-muted">{{ count($group['items']) }}</span>
                </h2>
                @if ($group['note'])
                    <p class="font-mono text-xs text-muted uppercase">{{ $group['note'] }}</p>
                @endif
            </div>
            @if ($group['intro'])
                <p class="mt-3 text-muted">{{ $group['intro'] }}</p>
            @endif

            @if ($group['items'] === [])
                <p class="mt-4 text-muted">{{ __('analysis.results.none') }}</p>
            @else
                <ul class="ledger border-b border-rule">
                    @foreach ($group['items'] as $item)
                        <li>@include('partials.rule-item', ['item' => $item, 'policyLanguage' => $policyLanguage])</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    <section aria-labelledby="context-heading" class="mt-16">
        <h2 id="context-heading" class="border-b-2 border-ink pb-3 font-serif text-3xl font-medium">{{ __('analysis.results.context_heading') }}</h2>
        <dl class="divide-y divide-rule border-b border-rule">
            @foreach ($report->context as $answer)
                <div class="grid gap-1 py-3 sm:grid-cols-[minmax(0,1fr)_10rem] sm:gap-8">
                    <dt class="text-ink-soft">{{ $answer->question->question->in($locale) }}</dt>
                    <dd class="font-mono text-sm font-medium sm:text-right">{{ __('home.answers.'.$answer->answer->value) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
</x-layout>
