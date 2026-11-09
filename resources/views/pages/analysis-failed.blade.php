@php
    /** @var \App\Models\Analysis $analysis */
    $code = $analysis->error_code ?? 'internal_error';
    $message = \Illuminate\Support\Facades\Lang::has('analysis.errors.'.$code) ? __('analysis.errors.'.$code) : __('analysis.errors.internal_error');
@endphp

<x-layout :title="__('analysis.failed.title')" noindex>
    <div class="max-w-2xl">
        <p class="eyebrow text-rubric">{{ __('analysis.failed.eyebrow') }}</p>
        <h1 class="mt-4 font-serif text-5xl leading-none font-medium tracking-tight">{{ __('analysis.failed.title') }}</h1>

        <div role="alert" class="mt-8 border-l-4 border-rubric bg-sheet px-5 py-4 text-lg">
            <p>{{ $message }}</p>
        </div>

        @if ($analysis->expires_at)
            <p class="mt-4 font-mono text-xs text-muted">{{ __('analysis.failed.expires', ['time' => $analysis->expires_at->diffForHumans()]) }}</p>
        @endif

        <div class="mt-10 flex flex-wrap items-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-primary">{{ __('analysis.failed.retry') }} <span aria-hidden="true">→</span></a>

            <form method="POST" action="{{ route('analyses.destroy', $analysis->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-quiet hover:border-rubric hover:text-rubric">{{ __('analysis.delete') }}</button>
            </form>
        </div>
    </div>
</x-layout>
