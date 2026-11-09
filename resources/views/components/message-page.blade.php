@props(['title', 'eyebrow' => null])

{{-- A simple page with a heading, a message and a way back to the form. --}}
<x-layout :title="$title" noindex>
    <div class="max-w-2xl">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-4 font-serif text-5xl leading-none font-medium tracking-tight">{{ $title }}</h1>
        <p class="mt-6 font-serif text-xl leading-relaxed text-ink-soft">{{ $slot }}</p>
        <a href="{{ route('home') }}" class="btn btn-primary mt-10">{{ __('ui.new_analysis') }} <span aria-hidden="true">→</span></a>
    </div>
</x-layout>
