@php
    /** @var \App\Models\Analysis $analysis */
    $code = $analysis->error_code ?? 'internal_error';
    $message = \Illuminate\Support\Facades\Lang::has('analysis.errors.'.$code) ? __('analysis.errors.'.$code) : __('analysis.errors.internal_error');
@endphp

<x-layout :title="__('analysis.failed.title')" noindex>
    <div class="max-w-2xl">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('analysis.failed.title') }}</h1>

        <div role="alert" class="mt-6 rounded-lg border-2 border-rose-700 bg-rose-50 p-4 text-rose-900">
            <p>{{ $message }}</p>
        </div>

        @if ($analysis->expires_at)
            <p class="mt-6 text-slate-700">{{ __('analysis.failed.expires', ['time' => $analysis->expires_at->diffForHumans()]) }}</p>
        @endif

        <div class="mt-8 flex flex-wrap items-center gap-4">
            <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center rounded-lg bg-accent-700 px-6 font-semibold text-white hover:bg-accent-800">{{ __('analysis.failed.retry') }}</a>

            <form method="POST" action="{{ route('analyses.destroy', $analysis->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex min-h-12 items-center rounded-lg border border-slate-400 bg-white px-5 font-medium text-slate-800 hover:bg-slate-100">{{ __('analysis.delete') }}</button>
            </form>
        </div>
    </div>
</x-layout>
