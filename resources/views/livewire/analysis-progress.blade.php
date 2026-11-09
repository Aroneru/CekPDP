<div wire:poll.2s="check" class="max-w-2xl">
    <p class="eyebrow">{{ __('analysis.progress.eyebrow') }}</p>
    <h1 class="mt-4 font-serif text-5xl leading-none font-medium tracking-tight">{{ __('analysis.progress.title') }}</h1>
    <p class="mt-5 font-serif text-xl leading-relaxed text-ink-soft italic">{{ __('analysis.progress.intro') }}</p>

    @if ($analysis)
        <div class="mt-10 border-t-2 border-ink pt-5">
            <div class="flex items-baseline justify-between gap-4">
                <label for="analysis-progress" class="eyebrow">{{ __('analysis.progress.label') }}</label>
                <span class="font-mono text-sm tabular-nums" aria-hidden="true">{{ $analysis->progress }}%</span>
            </div>
            <progress id="analysis-progress" class="progress-bar mt-3" max="100" value="{{ $analysis->progress }}"></progress>

            {{-- Announced politely, and only when the stage changes. --}}
            <p role="status" class="mt-4 font-medium">
                {{ __('analysis.progress.current', ['stage' => __('analysis.stages.'.$analysis->stage->value)]) }}
            </p>

            <ol class="mt-6 border-b border-rule">
                @foreach ($stages as $index => $stage)
                    @php
                        $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'pending');
                    @endphp
                    <li @class([
                        'grid grid-cols-[1.25rem_1.5rem_minmax(0,1fr)_auto] items-center gap-3 border-t border-rule py-3',
                        'text-muted' => $state === 'pending',
                        'font-semibold' => $state === 'current',
                    ]) @if ($state === 'current') aria-current="step" @endif>
                        <x-status-icon :status="$state" :class="\Illuminate\Support\Arr::toCssClasses([
                            'size-4',
                            'text-ink' => $state !== 'pending',
                            'text-rule-strong' => $state === 'pending',
                        ])" />
                        <span class="font-mono text-xs text-muted" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span>{{ __('analysis.stages.'.$stage->value) }}</span>
                        <span class="font-mono text-xs font-normal text-muted">({{ __('analysis.stage_state.'.$state) }})</span>
                    </li>
                @endforeach
            </ol>

            @if ($analysis->stage === \App\Enums\AnalysisStage::Queued && $analysis->created_at?->lt(now()->subMinute()))
                <p class="mt-6 border-l-4 border-review bg-review-soft px-4 py-3 text-sm">{{ __('analysis.progress.stalled') }}</p>
            @endif
        </div>
    @endif

    <noscript>
        <p class="mt-6 font-mono text-xs text-muted">{{ __('analysis.progress.noscript') }}</p>
    </noscript>
</div>
