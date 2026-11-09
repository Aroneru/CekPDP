<div wire:poll.2s="check" class="max-w-2xl">
    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('analysis.progress.title') }}</h1>
    <p class="mt-3 text-lg text-slate-700">{{ __('analysis.progress.intro') }}</p>

    @if ($analysis)
        <div class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <label for="analysis-progress" class="block font-medium text-slate-900">{{ __('analysis.progress.label') }}</label>
            <progress id="analysis-progress" class="progress-bar mt-3" max="100" value="{{ $analysis->progress }}"></progress>

            {{-- Announced politely, and only when the stage changes. --}}
            <p role="status" class="mt-3 text-slate-700">
                {{ __('analysis.progress.current', ['stage' => __('analysis.stages.'.$analysis->stage->value)]) }}
            </p>

            <ol class="mt-6 space-y-3">
                @foreach ($stages as $index => $stage)
                    @php
                        $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'pending');
                    @endphp
                    <li @class([
                        'flex items-center gap-3',
                        'text-slate-900' => $state !== 'pending',
                        'text-slate-500' => $state === 'pending',
                        'font-semibold' => $state === 'current',
                    ]) @if ($state === 'current') aria-current="step" @endif>
                        <x-status-icon :status="$state" :class="\Illuminate\Support\Arr::toCssClasses([
                            'size-5 shrink-0',
                            'text-emerald-700' => $state === 'done',
                            'text-accent-700' => $state === 'current',
                            'text-slate-400' => $state === 'pending',
                        ])" />
                        <span>{{ __('analysis.stages.'.$stage->value) }}</span>
                        <span class="sr-only">({{ __('analysis.stage_state.'.$state) }})</span>
                    </li>
                @endforeach
            </ol>

            @if ($analysis->stage === \App\Enums\AnalysisStage::Queued && $analysis->created_at?->lt(now()->subMinute()))
                <p class="mt-6 text-sm text-slate-600">{{ __('analysis.progress.stalled') }}</p>
            @endif
        </div>
    @endif

    <noscript>
        <p class="mt-6 text-sm text-slate-600">{{ __('analysis.progress.noscript') }}</p>
    </noscript>
</div>
