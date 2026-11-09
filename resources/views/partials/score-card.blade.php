@php
    /** @var \App\Domain\Scoring\CoverageScore $score */
    $legal = $score->legal;
    $best = $score->bestPractice;
@endphp

{{-- Neutral colours on purpose: the figure measures disclosure, it is not a verdict. --}}
<section aria-labelledby="score-heading" class="rounded-lg border border-slate-300 bg-white p-6">
    <h2 id="score-heading" class="text-lg font-semibold text-slate-900">{{ __('analysis.score.title') }}</h2>

    @if ($score->isWithheld())
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ __('analysis.score.withheld_label') }}</p>
        <p class="mt-2 text-slate-800">
            {{ __('analysis.score.withheld.'.$score->withheld->value, [
                'count' => $legal->needsReview,
                'total' => $legal->total(),
                'share' => $score->reviewSharePercent(),
                'threshold' => $score->reviewThresholdPercent,
            ]) }}
        </p>
    @else
        <p class="mt-3 text-5xl font-semibold tracking-tight text-slate-900">{{ $score->percent }}%</p>
        <p class="mt-3 font-mono text-slate-800">
            {{ __('analysis.score.arithmetic', [
                'addressed' => $legal->addressed,
                'partial' => $legal->partiallyAddressed,
                'evaluated' => $legal->evaluated(),
                'percent' => $score->percent,
            ]) }}
        </p>
        @if ($legal->needsReview > 0)
            <p class="mt-1 text-sm text-slate-700">{{ __('analysis.score.excluded', ['count' => $legal->needsReview]) }}</p>
        @endif
    @endif

    <p class="mt-4 text-sm text-slate-700">{{ __('analysis.score.explanation') }}</p>
    <p class="mt-2 text-sm text-slate-600">{{ __('analysis.score.formula') }}</p>
    <p class="mt-2 text-sm text-slate-600">{{ __('analysis.score.best_practices', ['addressed' => $best->addressed, 'total' => $best->total()]) }}</p>
</section>
