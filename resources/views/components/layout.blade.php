@props(['title' => null, 'noindex' => false])

@php
    $activeRuleset = app(\App\Domain\Rules\RulesetRepository::class)->active();
    $locale = app()->getLocale();
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
        @endif

        <title>{{ $title ? $title.' · CekPDP' : 'CekPDP' }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#main" class="skip-link">{{ __('ui.skip_to_content') }}</a>

        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6">
                <a href="{{ route('home') }}" class="group flex flex-col rounded-md py-1">
                    <span class="text-xl font-semibold tracking-tight text-accent-800 group-hover:underline">CekPDP</span>
                    <span class="text-sm text-slate-600">{{ __('ui.tagline') }}</span>
                </a>

                <form method="POST" action="{{ route('locale.update') }}">
                    @csrf
                    <div role="group" aria-label="{{ __('ui.language.label') }}" class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5">
                        @foreach (['id', 'en'] as $option)
                            <button
                                type="submit"
                                name="locale"
                                value="{{ $option }}"
                                lang="{{ $option }}"
                                aria-pressed="{{ $locale === $option ? 'true' : 'false' }}"
                                @class([
                                    'min-h-11 rounded-md px-3 text-sm font-medium',
                                    'bg-accent-700 text-white' => $locale === $option,
                                    'text-slate-700 hover:bg-slate-100' => $locale !== $option,
                                ])
                            >{{ __('ui.language.'.$option) }}</button>
                        @endforeach
                    </div>
                </form>
            </div>
        </header>

        <aside aria-label="{{ __('ui.notice.label') }}" class="border-b border-amber-200 bg-amber-50">
            <p class="mx-auto flex max-w-5xl items-start gap-2 px-4 py-3 text-sm text-amber-950 sm:px-6">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-4 shrink-0"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                <span>{{ __('ui.notice.body') }}</span>
            </p>
        </aside>

        <main id="main" tabindex="-1" class="mx-auto max-w-5xl px-4 py-8 focus:outline-none sm:px-6">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl space-y-1 px-4 py-6 text-sm text-slate-600 sm:px-6">
                <p>{{ __('ui.footer.ruleset', ['version' => $activeRuleset->version, 'date' => $activeRuleset->lastReviewed->locale($locale)->translatedFormat('j F Y')]) }}</p>
                <p>{{ __('ui.footer.scope') }}</p>
                <p>{{ __('ui.footer.translation') }}</p>
            </div>
        </footer>
    </body>
</html>
