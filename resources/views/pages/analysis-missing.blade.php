<x-layout :title="__('analysis.missing.title')" noindex>
    <div class="max-w-2xl">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('analysis.missing.title') }}</h1>
        <p class="mt-4 text-lg text-slate-700">{{ __('analysis.missing.body', ['minutes' => $retentionMinutes]) }}</p>
        <a href="{{ route('home') }}" class="mt-8 inline-flex min-h-12 items-center rounded-lg bg-accent-700 px-6 font-semibold text-white hover:bg-accent-800">{{ __('ui.new_analysis') }}</a>
    </div>
</x-layout>
