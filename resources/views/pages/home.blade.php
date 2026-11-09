@php
    /** @var \App\Domain\Rules\Ruleset $ruleset */
    $locale = app()->getLocale();
    $minFormatted = \Illuminate\Support\Number::format($minCharacters, locale: $locale);
    $maxFormatted = \Illuminate\Support\Number::format($maxCharacters, locale: $locale);
    $activeRules = $ruleset->activeRules();
    $legalCount = count(array_filter($activeRules, fn ($rule) => $rule->requirementType === \App\Enums\RequirementType::Legal));
    $fieldAnchors = ['text' => 'policy-text', 'acknowledge' => 'acknowledge', 'context' => 'context-heading', 'form' => 'policy-text'];
    $fieldErrors = function (string $field) use ($errors): array {
        $messages = $errors->get($field);

        foreach ($errors->get($field.'.*') as $nested) {
            $messages = [...$messages, ...(array) $nested];
        }

        return $messages;
    };
    $sampleLabel = fn ($sample): string => \Illuminate\Support\Facades\Lang::has('home.samples.'.$sample->slug) ? __('home.samples.'.$sample->slug) : $sample->title;
@endphp

<x-layout :title="__('home.title')">
    @if (session('status'))
        <p role="status" class="mb-10 border-l-4 border-addressed bg-sheet px-5 py-4">{{ session('status') }}</p>
    @endif

    {{-- Masthead --}}
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-16">
        <div>
            <p class="eyebrow">{{ __('home.eyebrow') }}</p>
            <h1 class="mt-5 max-w-3xl font-serif text-[2.5rem] leading-[1.05] font-medium tracking-tight text-balance sm:text-6xl">{{ __('home.title') }}</h1>
            <p class="mt-6 max-w-2xl font-serif text-xl leading-relaxed text-ink-soft italic">{{ __('home.intro', ['count' => count($activeRules)]) }}</p>
            <p class="mt-4 max-w-2xl text-muted">{{ __('home.scope') }}</p>
        </div>

        <dl class="self-end border-t-2 border-ink font-mono text-sm">
            @foreach ([
                __('home.facts.version') => $ruleset->version,
                __('home.facts.reviewed') => $ruleset->lastReviewed->locale($locale)->translatedFormat('j M Y'),
                __('home.facts.items') => __('home.facts.items_value', ['legal' => $legalCount, 'best' => count($activeRules) - $legalCount]),
                __('home.facts.method') => $provider === 'mock' ? __('home.facts.method_mock') : __('home.facts.method_ai'),
            ] as $term => $value)
                <div class="flex justify-between gap-4 border-b border-rule py-2.5">
                    <dt class="text-muted">{{ $term }}</dt>
                    <dd class="text-right">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    @if ($errors->any())
        <div id="error-summary" role="alert" tabindex="-1" aria-labelledby="error-summary-title" class="mt-12 border-l-4 border-rubric bg-sheet p-5">
            <h2 id="error-summary-title" class="font-semibold text-rubric">{{ __('home.errors_heading') }}</h2>
            <ul class="mt-2 space-y-1">
                @foreach ($errors->keys() as $field)
                    @foreach ($errors->get($field) as $message)
                        <li><a class="link" href="#{{ $fieldAnchors[explode('.', $field)[0]] ?? 'policy-text' }}">{{ $message }}</a></li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-14 grid gap-12 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-16">
        <form method="POST" action="{{ route('analyses.store') }}" class="space-y-14" data-submit-once>
            @csrf

            {{-- Step 1: the text --}}
            <section aria-labelledby="text-heading" class="grid gap-x-6 gap-y-5 border-t border-ink pt-6 md:grid-cols-[3rem_minmax(0,1fr)]">
                <span aria-hidden="true" class="hidden font-serif text-4xl leading-none text-rubric md:block">1</span>
                <div class="space-y-5">
                    <div>
                        <h2 id="text-heading" class="font-serif text-2xl font-medium">
                            <span aria-hidden="true" class="mr-1 text-rubric md:hidden">1</span>
                            <label for="policy-text">{{ __('home.text.label') }}</label>
                        </h2>
                        <p id="policy-text-hint" class="mt-1 text-sm text-muted">{{ __('home.text.hint', ['min' => $minFormatted, 'max' => $maxFormatted]) }}</p>
                    </div>

                    @if ($samples !== [])
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <span class="text-sm text-muted">{{ __('home.samples.label') }}</span>
                            <ul class="flex flex-wrap gap-2">
                                @foreach ($samples as $option)
                                    <li>
                                        <a
                                            href="{{ route('home', ['sample' => $option->slug]) }}#policy-text"
                                            @if ($sample?->slug === $option->slug) aria-current="true" @endif
                                            @class([
                                                'inline-flex min-h-11 items-center gap-2 border px-3 text-sm transition-colors',
                                                'border-ink bg-ink text-sheet' => $sample?->slug === $option->slug,
                                                'border-rule-strong hover:border-ink hover:bg-sheet' => $sample?->slug !== $option->slug,
                                            ])
                                        ><span aria-hidden="true" class="font-mono text-xs uppercase opacity-70">{{ $option->language === 'en' ? 'en' : 'id' }}</span>{{ $sampleLabel($option) }}<span class="sr-only"> ({{ __('analysis.results.languages.'.($option->language === 'en' ? 'en' : 'id')) }})</span></a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @if ($sample)
                            <p role="status" class="text-sm text-addressed">{{ __('home.samples.loaded', ['title' => $sampleLabel($sample)]) }}</p>
                        @endif
                    @endif

                    @foreach ($fieldErrors('text') as $message)
                        <p id="policy-text-error" class="flex items-start gap-2 text-sm font-medium text-rubric">
                            <x-status-icon status="not_found" class="mt-0.5 size-4 shrink-0" />{{ $message }}
                        </p>
                    @endforeach

                    <div>
                        <textarea
                            id="policy-text"
                            name="text"
                            rows="14"
                            required
                            minlength="{{ $minCharacters }}"
                            spellcheck="false"
                            autocomplete="off"
                            aria-describedby="policy-text-hint{{ $errors->has('text') ? ' policy-text-error' : '' }} policy-text-count"
                            @if ($errors->has('text')) aria-invalid="true" @endif
                            placeholder="{{ __('home.text.placeholder') }}"
                            class="legal-pad field"
                        >{{ $text }}</textarea>
                        <p id="policy-text-count" class="mt-2 text-right font-mono text-xs text-muted" data-character-count="policy-text" data-template="{{ __('home.text.count', ['count' => ':count']) }}" aria-live="off"></p>
                    </div>
                </div>
            </section>

            {{-- Step 2: context --}}
            <section aria-labelledby="context-heading" class="grid gap-x-6 gap-y-5 border-t border-ink pt-6 md:grid-cols-[3rem_minmax(0,1fr)]">
                <span aria-hidden="true" class="hidden font-serif text-4xl leading-none text-rubric md:block">2</span>
                <div>
                    <h2 id="context-heading" class="font-serif text-2xl font-medium">
                        <span aria-hidden="true" class="mr-1 text-rubric md:hidden">2</span>
                        {{ __('home.context.heading') }}
                        <span class="ml-1 font-sans text-sm font-normal text-muted">{{ __('home.context.optional') }}</span>
                    </h2>
                    <p id="context-intro" class="mt-1 text-sm text-muted">{{ __('home.context.intro') }}</p>
                    @foreach ($fieldErrors('context') as $message)
                        <p class="mt-3 text-sm font-medium text-rubric">{{ $message }}</p>
                    @endforeach

                    <div class="mt-4 border-b border-rule">
                        @foreach ($ruleset->contexts as $key => $question)
                            <fieldset
                                class="grid gap-x-8 gap-y-3 border-t border-rule py-5 md:grid-cols-[minmax(0,1fr)_auto]"
                                aria-describedby="context-intro{{ $question->help ? ' context-'.$key.'-help' : '' }}"
                            >
                                <legend class="float-left leading-snug font-medium md:col-start-1 md:row-start-1">{{ $question->question->in($locale) }}</legend>
                                @if ($question->help)
                                    <p id="context-{{ $key }}-help" class="font-mono text-xs text-muted md:col-start-1 md:row-start-2">{{ $question->help->in($locale) }}</p>
                                @endif
                                <div class="segmented self-start md:col-start-2 md:row-span-2 md:row-start-1 md:self-center">
                                    @foreach ($question->options as $option)
                                        <label>
                                            <input
                                                type="radio"
                                                name="context[{{ $key }}]"
                                                value="{{ $option->value }}"
                                                class="visually-hidden"
                                                @checked(($answers[$key] ?? $question->default->value) === $option->value)
                                            >
                                            {{ __('home.answers.'.$option->value) }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Step 3: disclosure and submit --}}
            <section aria-labelledby="disclosure-heading" class="grid gap-x-6 gap-y-5 border-t border-ink pt-6 md:grid-cols-[3rem_minmax(0,1fr)]">
                <span aria-hidden="true" class="hidden font-serif text-4xl leading-none text-rubric md:block">3</span>
                <div>
                    <h2 id="disclosure-heading" class="font-serif text-2xl font-medium"><span aria-hidden="true" class="mr-1 text-rubric md:hidden">3</span>{{ __('home.disclosure.heading') }}</h2>

                    <ul id="disclosure-points" class="mt-4 space-y-3">
                        @foreach ([
                            $provider === 'mock' ? __('home.disclosure.mock') : __('home.disclosure.ai', ['provider' => 'Anthropic']),
                            __('home.disclosure.confidential'),
                            __('home.disclosure.retention', ['minutes' => $retentionMinutes]),
                        ] as $index => $point)
                            <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] text-ink-soft">
                                <span aria-hidden="true" class="font-mono text-sm text-rubric">{{ chr(97 + $index) }}.</span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @foreach ($fieldErrors('acknowledge') as $message)
                        <p id="acknowledge-error" class="mt-5 text-sm font-medium text-rubric">{{ $message }}</p>
                    @endforeach
                    <label class="mt-6 flex cursor-pointer items-start gap-4 border border-rule-strong bg-sheet p-4 hover:border-ink">
                        <input
                            id="acknowledge"
                            type="checkbox"
                            name="acknowledge"
                            value="1"
                            required
                            class="check field mt-0.5"
                            aria-describedby="disclosure-points{{ $errors->has('acknowledge') ? ' acknowledge-error' : '' }}"
                            @if ($errors->has('acknowledge')) aria-invalid="true" @endif
                        >
                        <span class="font-medium">{{ __('home.disclosure.acknowledge') }}</span>
                    </label>

                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                        <button type="submit" data-busy-label="{{ __('home.submitting') }}" class="btn btn-primary min-w-56 text-base">
                            {{ __('home.submit') }}
                            <span aria-hidden="true">→</span>
                        </button>
                        <p class="text-sm text-muted">{{ __('home.submit_hint') }}</p>
                    </div>
                </div>
            </section>
        </form>

        {{-- Marginal notes --}}
        <aside aria-labelledby="about-heading" class="space-y-6 text-sm lg:border-l lg:border-rule lg:pl-8">
            <div>
                <h2 id="about-heading" class="eyebrow">{{ __('home.about.heading') }}</h2>
                <p class="mt-3 text-ink-soft">{{ __('home.about.ruleset', [
                    'version' => $ruleset->version,
                    'date' => $ruleset->lastReviewed->locale($locale)->translatedFormat('j F Y'),
                    'legal' => $legalCount,
                    'best' => count($activeRules) - $legalCount,
                ]) }}</p>
            </div>

            <div>
                <h3 class="eyebrow">{{ __('home.about.regulations') }}</h3>
                <ul class="mt-3 space-y-5">
                    @foreach ($ruleset->instrument->implementingRegulations as $regulation)
                        <li>
                            <span class="stamp">{{ __('home.about.status.'.$regulation->status) }}</span>
                            <p class="mt-2 font-serif text-base leading-snug font-medium">{{ $regulation->reference }}</p>
                            <p class="mt-1 text-muted">
                                {{ $regulation->note->in($locale) }}
                                @unless ($regulation->usedByRules)
                                    {{ __('home.about.not_used') }}
                                @endunless
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</x-layout>
