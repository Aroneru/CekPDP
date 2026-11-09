@php
    /** @var \App\Domain\Rules\Ruleset $ruleset */
    $locale = app()->getLocale();
    $minFormatted = \Illuminate\Support\Number::format($minCharacters, locale: $locale);
    $maxFormatted = \Illuminate\Support\Number::format($maxCharacters, locale: $locale);
    $legalCount = count(array_filter($ruleset->activeRules(), fn ($rule) => $rule->requirementType === \App\Enums\RequirementType::Legal));
    $fieldAnchors = ['text' => 'policy-text', 'acknowledge' => 'acknowledge', 'context' => 'context-heading', 'form' => 'policy-text'];
    $fieldErrors = function (string $field) use ($errors): array {
        $messages = $errors->get($field);

        foreach ($errors->get($field.'.*') as $nested) {
            $messages = [...$messages, ...(array) $nested];
        }

        return $messages;
    };
@endphp

<x-layout :title="__('home.title')">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <div>
            @if (session('status'))
                <p role="status" class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-900">
                    {{ session('status') }}
                </p>
            @endif

            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ __('home.title') }}</h1>
            <p class="mt-3 max-w-prose text-lg text-slate-700">{{ __('home.intro', ['count' => count($ruleset->activeRules())]) }}</p>
            <p class="mt-2 max-w-prose text-slate-600">{{ __('home.scope') }}</p>

            @if ($errors->any())
                <div id="error-summary" role="alert" tabindex="-1" aria-labelledby="error-summary-title" class="mt-8 rounded-lg border-2 border-rose-700 bg-rose-50 p-4">
                    <h2 id="error-summary-title" class="font-semibold text-rose-900">{{ __('home.errors_heading') }}</h2>
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-rose-900">
                        @foreach ($errors->keys() as $field)
                            @foreach ($errors->get($field) as $message)
                                <li><a class="underline" href="#{{ $fieldAnchors[explode('.', $field)[0]] ?? 'policy-text' }}">{{ $message }}</a></li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('analyses.store') }}" class="mt-8 space-y-8" data-submit-once>
                @csrf

                <section aria-labelledby="text-heading" class="space-y-3">
                    <h2 id="text-heading" class="sr-only">{{ __('home.text.label') }}</h2>

                    @if ($samples !== [])
                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                            <p class="text-sm font-medium text-slate-800">{{ __('home.samples.label') }}</p>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($samples as $option)
                                    <li>
                                        <a
                                            href="{{ route('home', ['sample' => $option->slug]) }}#policy-text"
                                            @if ($sample?->slug === $option->slug) aria-current="true" @endif
                                            @class([
                                                'inline-flex min-h-11 items-center rounded-md border px-3 text-sm',
                                                'border-accent-700 bg-accent-50 font-medium text-accent-800' => $sample?->slug === $option->slug,
                                                'border-slate-300 text-slate-700 hover:border-accent-600 hover:text-accent-800' => $sample?->slug !== $option->slug,
                                            ])
                                        >{{ \Illuminate\Support\Facades\Lang::has('home.samples.'.$option->slug) ? __('home.samples.'.$option->slug) : $option->title }}</a>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($sample)
                                <p role="status" class="mt-3 text-sm text-slate-700">{{ __('home.samples.loaded', ['title' => \Illuminate\Support\Facades\Lang::has('home.samples.'.$sample->slug) ? __('home.samples.'.$sample->slug) : $sample->title]) }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label for="policy-text" class="block font-semibold text-slate-900">{{ __('home.text.label') }}</label>
                        <p id="policy-text-hint" class="text-sm text-slate-600">{{ __('home.text.hint', ['min' => $minFormatted, 'max' => $maxFormatted]) }}</p>
                        @foreach ($fieldErrors('text') as $message)
                            <p id="policy-text-error" class="flex items-start gap-1.5 text-sm font-medium text-rose-800">
                                <x-status-icon status="needs_human_review" class="mt-0.5 size-4 shrink-0" />{{ $message }}
                            </p>
                        @endforeach
                        <textarea
                            id="policy-text"
                            name="text"
                            rows="16"
                            required
                            minlength="{{ $minCharacters }}"
                            spellcheck="false"
                            autocomplete="off"
                            aria-describedby="policy-text-hint{{ $errors->has('text') ? ' policy-text-error' : '' }} policy-text-count"
                            @if ($errors->has('text')) aria-invalid="true" @endif
                            class="field block w-full rounded-lg border border-slate-400 bg-white p-3 font-mono text-base leading-relaxed text-slate-900 shadow-sm"
                        >{{ $text }}</textarea>
                        <p id="policy-text-count" class="text-sm text-slate-500" data-character-count="policy-text" data-template="{{ __('home.text.count', ['count' => ':count']) }}" aria-live="off"></p>
                    </div>
                </section>

                <fieldset class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 sm:p-6" aria-describedby="context-intro">
                    <legend id="context-heading" class="px-1 text-lg font-semibold text-slate-900">{{ __('home.context.heading') }}</legend>
                    <p id="context-intro" class="text-sm text-slate-600">{{ __('home.context.intro') }}</p>
                    @foreach ($fieldErrors('context') as $message)
                        <p class="text-sm font-medium text-rose-800">{{ $message }}</p>
                    @endforeach

                    @foreach ($ruleset->contexts as $key => $question)
                        <fieldset class="border-t border-slate-200 pt-4" @if ($question->help) aria-describedby="context-{{ $key }}-help" @endif>
                            <legend class="font-medium text-slate-900">{{ $question->question->in($locale) }}</legend>
                            @if ($question->help)
                                <p id="context-{{ $key }}-help" class="mt-1 text-sm text-slate-600">{{ $question->help->in($locale) }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1">
                                @foreach ($question->options as $option)
                                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            name="context[{{ $key }}]"
                                            value="{{ $option->value }}"
                                            class="size-5"
                                            @checked(($answers[$key] ?? $question->default->value) === $option->value)
                                        >
                                        <span>{{ __('home.answers.'.$option->value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </fieldset>

                <section aria-labelledby="disclosure-heading" class="rounded-lg border-2 border-accent-600 bg-accent-50 p-4 sm:p-6">
                    <h2 id="disclosure-heading" class="text-lg font-semibold text-accent-800">{{ __('home.disclosure.heading') }}</h2>
                    <ul id="disclosure-points" class="mt-3 list-disc space-y-2 ps-5 text-slate-800">
                        <li>{{ $provider === 'mock' ? __('home.disclosure.mock') : __('home.disclosure.ai', ['provider' => 'Anthropic']) }}</li>
                        <li>{{ __('home.disclosure.confidential') }}</li>
                        <li>{{ __('home.disclosure.retention', ['minutes' => $retentionMinutes]) }}</li>
                        <li>{{ __('ui.notice.body') }}</li>
                    </ul>

                    @foreach ($fieldErrors('acknowledge') as $message)
                        <p id="acknowledge-error" class="mt-4 text-sm font-medium text-rose-800">{{ $message }}</p>
                    @endforeach
                    <label class="mt-4 flex min-h-11 cursor-pointer items-start gap-3 font-medium text-slate-900">
                        <input
                            id="acknowledge"
                            type="checkbox"
                            name="acknowledge"
                            value="1"
                            required
                            class="mt-0.5 size-5 shrink-0"
                            aria-describedby="disclosure-points{{ $errors->has('acknowledge') ? ' acknowledge-error' : '' }}"
                            @if ($errors->has('acknowledge')) aria-invalid="true" @endif
                        >
                        <span>{{ __('home.disclosure.acknowledge') }}</span>
                    </label>
                </section>

                <button
                    type="submit"
                    data-busy-label="{{ __('home.submitting') }}"
                    class="inline-flex min-h-12 items-center rounded-lg bg-accent-700 px-6 text-base font-semibold text-white shadow-sm hover:bg-accent-800 disabled:cursor-wait disabled:opacity-75"
                >{{ __('home.submit') }}</button>
            </form>
        </div>

        <aside aria-labelledby="about-heading" class="space-y-4 text-sm text-slate-700 lg:pt-1">
            <h2 id="about-heading" class="text-base font-semibold text-slate-900">{{ __('home.about.heading') }}</h2>
            <p>{{ __('home.about.ruleset', [
                'version' => $ruleset->version,
                'date' => $ruleset->lastReviewed->locale($locale)->translatedFormat('j F Y'),
                'legal' => $legalCount,
                'best' => count($ruleset->activeRules()) - $legalCount,
            ]) }}</p>

            <h3 class="font-semibold text-slate-900">{{ __('home.about.regulations') }}</h3>
            <ul class="space-y-3">
                @foreach ($ruleset->instrument->implementingRegulations as $regulation)
                    <li>
                        <span class="font-medium text-slate-900">{{ $regulation->reference }}</span><br>
                        {{ $regulation->note->in($locale) }}
                        @unless ($regulation->usedByRules)
                            {{ __('home.about.not_used') }}
                        @endunless
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-layout>
