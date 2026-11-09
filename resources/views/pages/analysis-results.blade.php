@php
    /** @var \App\Domain\Reporting\Report $report */
    $locale = app()->getLocale();
    $score = $report->score;
    $policyLanguage = $report->language->language === \App\Enums\DocumentLanguage::English ? 'en' : 'id';
    $statuses = ['addressed', 'partially_addressed', 'not_found', 'needs_human_review'];
    $number = fn (int $value): string => \Illuminate\Support\Number::format($value, locale: $locale);
    $reviewItems = $report->needsReview();
@endphp

<x-layout :title="__('analysis.results.title')" noindex>
    <div class="space-y-10">
        <header class="space-y-4">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('analysis.results.title') }}</h1>

            @if ($report->isOffline())
                <p class="rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-slate-800">{{ __('analysis.results.offline') }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                @if ($report->expiresAt)
                    <p class="text-slate-700">{{ __('analysis.results.retention', ['time' => $report->expiresAt->diffForHumans()]) }}</p>
                @endif
                <form method="POST" action="{{ route('analyses.destroy', $report->analysisId) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-lg border border-slate-400 bg-white px-4 font-medium text-slate-800 hover:bg-slate-100">{{ __('analysis.delete') }}</button>
                </form>
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center text-accent-700 underline hover:text-accent-800">{{ __('ui.new_analysis') }}</a>
            </div>
        </header>

        <section aria-labelledby="summary-heading" class="grid gap-6 md:grid-cols-2">
            <h2 id="summary-heading" class="sr-only">{{ __('analysis.results.summary_heading') }}</h2>

            @include('partials.score-card', ['score' => $score])

            <div class="space-y-6">
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 rounded-lg border border-slate-200 bg-white p-6 text-sm">
                    <dt class="font-medium text-slate-900">{{ __('analysis.results.details.language') }}</dt>
                    <dd class="text-slate-700">{{ __('analysis.results.languages.'.$report->language->language->value) }}</dd>

                    <dt class="font-medium text-slate-900">{{ __('analysis.results.details.size') }}</dt>
                    <dd class="text-slate-700">{{ __('analysis.results.details.size_value', ['characters' => $number($report->characters), 'words' => $number($report->words), 'sections' => $number($report->sections)]) }}</dd>

                    <dt class="font-medium text-slate-900">{{ __('analysis.results.details.provider') }}</dt>
                    <dd class="text-slate-700">{{ \Illuminate\Support\Facades\Lang::has('analysis.results.providers.'.$report->provider) ? __('analysis.results.providers.'.$report->provider) : $report->provider }}</dd>

                    <dt class="font-medium text-slate-900">{{ __('analysis.results.details.ruleset') }}</dt>
                    <dd class="text-slate-700">{{ __('analysis.results.details.ruleset_value', ['version' => $report->ruleset->version, 'date' => $report->ruleset->lastReviewed->locale($locale)->translatedFormat('j F Y')]) }}</dd>

                    @if ($report->completedAt)
                        <dt class="font-medium text-slate-900">{{ __('analysis.results.details.completed') }}</dt>
                        <dd class="text-slate-700">{{ $report->completedAt->locale($locale)->translatedFormat('j F Y, H:i T') }}</dd>
                    @endif
                </dl>

                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <table class="w-full text-sm">
                        <caption class="px-4 pt-4 text-start font-semibold text-slate-900">{{ __('analysis.results.counts_caption') }}</caption>
                        <thead>
                            <tr class="border-b border-slate-200 text-start align-bottom">
                                <th scope="col" class="px-4 py-2 text-start font-medium text-slate-700">{{ __('analysis.results.counts_type') }}</th>
                                @foreach ($statuses as $status)
                                    <th scope="col" class="px-2 py-2 text-end font-medium text-slate-700">
                                        <span class="inline-flex items-center gap-1"><x-status-icon :status="$status" class="size-4" />{{ __('analysis.status.'.$status) }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['legal' => $score->legal, 'best_practice' => $score->bestPractice] as $type => $counts)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <th scope="row" class="px-4 py-2 text-start font-medium text-slate-900">{{ __('analysis.type.'.$type) }}</th>
                                    @foreach ($counts->toArray() as $count)
                                        <td class="px-2 py-2 text-end tabular-nums text-slate-800">{{ $count }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @if ($reviewItems !== [])
            <section aria-labelledby="review-heading" class="rounded-lg border border-violet-300 bg-violet-50 p-6">
                <h2 id="review-heading" class="text-lg font-semibold text-violet-950">{{ __('analysis.results.review_heading') }}</h2>
                <p class="mt-1 text-violet-950">{{ __('analysis.results.review_intro') }}</p>
                <ul class="mt-3 list-disc space-y-1 ps-5">
                    @foreach ($reviewItems as $item)
                        <li><a href="#rule-{{ $item->rule->id }}" class="text-violet-900 underline">{{ $item->rule->title->in($locale) }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @foreach (['legal' => $report->legal, 'best' => $report->bestPractices] as $group => $items)
            <section aria-labelledby="{{ $group }}-heading">
                <h2 id="{{ $group }}-heading" class="text-xl font-semibold text-slate-900">
                    {{ __('analysis.results.'.$group.'_heading') }} <span class="font-normal text-slate-600">({{ count($items) }})</span>
                </h2>
                @if ($items === [])
                    <p class="mt-3 text-slate-600">{{ __('analysis.results.none') }}</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($items as $item)
                            <li>@include('partials.rule-item', ['item' => $item, 'policyLanguage' => $policyLanguage])</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach

        @if ($report->notApplicable !== [])
            <section aria-labelledby="not-applicable-heading">
                <h2 id="not-applicable-heading" class="text-xl font-semibold text-slate-900">
                    {{ __('analysis.results.not_applicable_heading') }} <span class="font-normal text-slate-600">({{ count($report->notApplicable) }})</span>
                </h2>
                <p class="mt-1 text-slate-600">{{ __('analysis.results.not_applicable_intro') }}</p>
                <ul class="mt-4 space-y-3">
                    @foreach ($report->notApplicable as $item)
                        <li>@include('partials.rule-item', ['item' => $item, 'policyLanguage' => $policyLanguage])</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section aria-labelledby="context-heading">
            <h2 id="context-heading" class="text-xl font-semibold text-slate-900">{{ __('analysis.results.context_heading') }}</h2>
            <dl class="mt-4 space-y-3 rounded-lg border border-slate-200 bg-white p-6 text-sm">
                @foreach ($report->context as $answer)
                    <div>
                        <dt class="text-slate-700">{{ $answer->question->question->in($locale) }}</dt>
                        <dd class="font-medium text-slate-900">{{ __('home.answers.'.$answer->answer->value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>
</x-layout>
