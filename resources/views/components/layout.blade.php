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
        <meta name="theme-color" content="#1c1a16">
        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
        @endif

        <title>{{ $title ? $title.' · CekPDP' : 'CekPDP' }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="flex min-h-screen flex-col font-sans antialiased">
        <a href="#main" class="skip-link">{{ __('ui.skip_to_content') }}</a>

        <aside aria-label="{{ __('ui.notice.label') }}" class="bg-ink text-sheet">
            <p class="mx-auto flex max-w-6xl flex-wrap items-baseline gap-x-3 gap-y-1 px-5 py-2.5 text-sm sm:px-8">
                <span class="font-mono text-xs tracking-[0.08em] text-[#e9b8a8] uppercase">{{ __('ui.notice.short') }}</span>
                <span class="text-[#e8e3d6]">{{ __('ui.notice.body') }}</span>
            </p>
        </aside>

        <header class="border-b border-rule">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-8 gap-y-3 px-5 py-5 sm:px-8">
                <a href="{{ route('home') }}" class="group flex items-center gap-3" aria-label="CekPDP, {{ __('ui.new_analysis') }}">
                    <svg aria-hidden="true" viewBox="0 0 32 32" class="size-8 shrink-0">
                        <rect x="1.5" y="1.5" width="29" height="29" fill="none" stroke="var(--color-ink)" stroke-width="2.5"/>
                        <path d="M8 16.5l5.5 5.5L25 9.5" fill="none" stroke="var(--color-rubric)" stroke-width="3.5" stroke-linecap="square"/>
                    </svg>
                    <span class="flex flex-col">
                        <span class="font-serif text-2xl leading-none font-semibold tracking-tight">Cek<span class="text-rubric">PDP</span></span>
                        <span class="mt-1 hidden text-xs text-muted sm:block">{{ __('ui.tagline') }}</span>
                    </span>
                </a>

                <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-3">
                    @csrf
                    <span class="eyebrow hidden sm:inline" aria-hidden="true">{{ __('ui.language.short') }}</span>
                    <div role="group" aria-label="{{ __('ui.language.label') }}" class="flex border border-rule-strong">
                        @foreach (['id', 'en'] as $option)
                            <button
                                type="submit"
                                name="locale"
                                value="{{ $option }}"
                                lang="{{ $option }}"
                                aria-pressed="{{ $locale === $option ? 'true' : 'false' }}"
                                title="{{ __('ui.language.'.$option) }}"
                                @class([
                                    'min-h-11 min-w-12 px-3 font-mono text-sm uppercase',
                                    'bg-ink text-sheet' => $locale === $option,
                                    'text-ink-soft hover:bg-shade' => $locale !== $option,
                                ])
                            ><span class="sr-only">{{ __('ui.language.'.$option) }}</span><span aria-hidden="true">{{ $option }}</span></button>
                        @endforeach
                    </div>
                </form>
            </div>
        </header>

        <main id="main" tabindex="-1" class="mx-auto w-full max-w-6xl flex-1 px-5 pt-10 pb-20 focus:outline-none sm:px-8 sm:pt-14">
            {{ $slot }}
        </main>

        <footer class="border-t border-rule">
            <div class="mx-auto grid max-w-6xl gap-6 px-5 py-8 text-sm text-muted sm:px-8 md:grid-cols-3">
                <p>
                    <span class="font-serif text-base font-semibold text-ink">Cek<span class="text-rubric">PDP</span></span><br>
                    {{ __('ui.footer.scope') }}
                </p>
                <p class="font-mono text-xs leading-relaxed">
                    {{ __('ui.footer.ruleset', ['version' => $activeRuleset->version, 'date' => $activeRuleset->lastReviewed->locale($locale)->translatedFormat('j F Y')]) }}
                </p>
                <p>{{ __('ui.footer.translation') }}</p>
            </div>
        </footer>
    </body>
</html>
