@php
    /** @var \App\Domain\Reporting\ReportItem $item */
    /** @var string $policyLanguage 'id' or 'en': the language to show example wording in */
    $locale = app()->getLocale();
    $rule = $item->rule;
    $basis = $item->basisReferences();
    $related = $item->relatedReferences();
@endphp

<details id="rule-{{ $rule->id }}" class="group scroll-mt-6">
    <summary class="grid grid-cols-[1rem_minmax(0,1fr)] items-start gap-x-4 gap-y-1.5 px-3 py-4 sm:px-4 md:grid-cols-[1rem_13rem_minmax(0,1fr)_auto] md:items-center">
        <svg aria-hidden="true" viewBox="0 0 16 16" class="chevron mt-1 size-4 text-muted md:mt-0" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m6 3.5 4.5 4.5L6 12.5" /></svg>
        <x-status-badge :status="$item->status" class="md:col-start-2" />
        <span class="col-start-2 md:col-start-3">
            <span class="font-serif text-lg leading-snug font-medium">{{ $rule->title->in($locale) }}</span>
            @if ($rule->isInterpretive())
                <span class="ml-2 inline-block border border-rule-strong px-1.5 align-middle font-mono text-[0.6875rem] tracking-wide text-muted uppercase">{{ __('analysis.interpretive') }}</span>
            @endif
        </span>
        <span class="col-start-2 flex flex-wrap items-baseline gap-x-3 font-mono text-xs text-muted md:col-start-4 md:justify-end md:text-right">
            @if ($basis !== [])
                <span class="text-rubric">{{ $basis[0]->provision->citation->in($locale) }} @if (count($basis) > 1)<span class="text-muted">+{{ count($basis) - 1 }}</span>@endif</span>
            @endif
            <span>{{ $rule->id }}</span>
        </span>
    </summary>

    <div class="grid gap-x-12 gap-y-8 px-3 pt-2 pb-8 sm:px-4 md:pl-12 lg:grid-cols-[minmax(0,1fr)_17rem]">
        {{-- Findings --}}
        <div class="space-y-7">
            <p class="max-w-prose text-ink-soft">{{ $rule->explanation->in($locale) }}</p>

            @if ($item->notApplicableBecause !== [])
                <div>
                    <h3 class="eyebrow">{{ __('analysis.item.not_applicable_because') }}</h3>
                    <ul class="mt-2 space-y-1">
                        @foreach ($item->notApplicableBecause as $answer)
                            <li>{{ __('analysis.item.answer', ['question' => $answer->question->question->in($locale), 'answer' => __('home.answers.'.$answer->answer->value)]) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($item->reason)
                <div>
                    <h3 class="eyebrow">{{ __('analysis.item.reason') }}</h3>
                    <p class="mt-2 max-w-prose">{{ $item->reason->in($locale) }}</p>
                    @if ($item->source === \App\Enums\EvaluationSource::LanguageCheck)
                        <p class="mt-1 font-mono text-xs text-muted">{{ __('analysis.item.language_check') }}</p>
                    @endif
                </div>
            @endif

            @if ($item->wasDowngraded() && $item->modelStatus)
                <p class="max-w-prose border-l-4 border-review bg-review-soft px-4 py-3 text-sm">
                    {{ __('analysis.item.downgraded.'.$item->verification->downgrade->value, ['status' => __('analysis.status.'.$item->modelStatus->value)]) }}
                </p>
            @endif

            @if ($item->applicable && $item->source !== \App\Enums\EvaluationSource::LanguageCheck)
                <div>
                    <h3 class="eyebrow">{{ __('analysis.item.evidence') }}</h3>
                    @forelse ($item->quotes as $quote)
                        <figure class="mt-3 grid gap-1 sm:grid-cols-[3.5rem_minmax(0,1fr)] sm:gap-4">
                            <figcaption class="pt-1 font-mono text-xs text-muted sm:order-first">
                                @if ($quote->verified)
                                    <span title="{{ __('analysis.item.section', ['section' => $quote->sectionId ?? $quote->citedSectionId]) }}">§ {{ $quote->sectionId ?? $quote->citedSectionId }}</span>
                                    @if ($quote->sectionWasCorrected())
                                        <span class="block">{{ __('analysis.item.section_corrected', ['actual' => $quote->sectionId, 'cited' => $quote->citedSectionId]) }}</span>
                                    @endif
                                @else
                                    <span class="text-review">{{ __('analysis.item.unverified') }}</span>
                                @endif
                            </figcaption>
                            <blockquote @class(['excerpt max-w-prose', 'excerpt-unverified' => ! $quote->verified]) lang="{{ $policyLanguage }}">{{ $quote->quote }}</blockquote>
                        </figure>
                    @empty
                        <p class="mt-2 text-muted">{{ __('analysis.item.no_evidence') }}</p>
                    @endforelse
                </div>
            @endif

            <div>
                <h3 class="eyebrow">{{ __('analysis.item.look_for') }}</h3>
                <ul class="mt-2 max-w-prose space-y-1.5">
                    @foreach ($rule->lookFor->in($locale) as $point)
                        <li class="grid grid-cols-[1rem_minmax(0,1fr)] gap-2 text-ink-soft">
                            <span aria-hidden="true" class="mt-2 size-1.5 bg-rule-strong"></span>
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($item->hasGap())
                <div>
                    <h3 class="eyebrow text-rubric">{{ __('analysis.item.next_step') }}</h3>
                    <p class="mt-2 max-w-prose">{{ $rule->nextStep->in($locale) }}</p>
                </div>

                <div class="draft max-w-prose">
                    <h3 class="flex flex-wrap items-center gap-3">
                        <span class="eyebrow text-ink">{{ __('analysis.item.example') }}</span>
                        <span class="stamp">{{ __('analysis.item.example_label') }}</span>
                    </h3>
                    <p class="mt-3 font-serif text-lg leading-relaxed" lang="{{ $policyLanguage }}">{{ $rule->exampleWording->in($policyLanguage) }}</p>
                </div>
            @endif
        </div>

        {{-- Margin: legal basis and notes --}}
        <div class="space-y-7 text-sm lg:border-l lg:border-rule lg:pl-6">
            @foreach (['basis' => $basis, 'related' => $related] as $role => $references)
                @if ($references !== [])
                    <div>
                        <h3 class="eyebrow">{{ __('analysis.item.'.$role) }}</h3>
                        <ul class="mt-3 space-y-4">
                            @foreach ($references as $reference)
                                <li>
                                    <p class="font-serif text-base leading-snug font-medium">
                                        <span @class(['text-rubric' => $role === 'basis'])>{{ $reference->provision->citation->in($locale) }}</span>
                                        @if ($reference->pinpoint)
                                            <span class="font-normal text-ink-soft">{{ $reference->pinpoint->in($locale) }}</span>
                                        @endif
                                    </p>
                                    @if ($reference->provision->modifiedBy)
                                        <p class="mt-0.5 text-xs text-muted">{{ __('analysis.item.modified_by', ['reference' => $reference->provision->modifiedBy]) }}</p>
                                    @endif
                                    <p class="mt-1 flex flex-col gap-0.5">
                                        @if ($reference->officialUrl)
                                            <a href="{{ $reference->officialUrl }}" rel="noopener noreferrer" class="link">{{ __('analysis.item.official_text', ['page' => $reference->page()]) }} <span aria-hidden="true">↗</span></a>
                                        @endif
                                        @if ($reference->readerUrl)
                                            <a href="{{ $reference->readerUrl }}" rel="noopener noreferrer" class="link text-muted">{{ __('analysis.item.reader') }} <span aria-hidden="true">↗</span></a>
                                        @endif
                                    </p>
                                    <details class="mt-1.5">
                                        <summary class="inline-flex min-h-11 cursor-pointer items-center font-mono text-xs text-muted uppercase underline decoration-rule-strong underline-offset-4 hover:text-ink">{{ __('analysis.item.provision_text') }}</summary>
                                        <blockquote class="excerpt mt-1 text-base" lang="id">{{ $reference->provision->text->id }}</blockquote>
                                        @if ($locale !== 'id')
                                            <p class="mt-2 text-xs text-muted">{{ __('analysis.item.unofficial_translation') }} {{ $reference->provision->text->en }}</p>
                                        @endif
                                    </details>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach

            @if ($rule->interpretationNote)
                <div>
                    <h3 class="eyebrow">{{ __('analysis.item.interpretation') }}</h3>
                    <p class="mt-2 font-serif text-base leading-snug text-ink-soft italic">{{ $rule->interpretationNote->in($locale) }}</p>
                </div>
            @endif

            @if ($item->override)
                <div>
                    <h3 class="eyebrow">{{ __('analysis.item.override') }}</h3>
                    <p class="mt-2 font-serif text-base leading-snug text-ink-soft italic">{{ $item->override->reason->in($locale) }}</p>
                </div>
            @endif

            <details>
                <summary class="inline-flex min-h-11 cursor-pointer items-center font-mono text-xs text-muted uppercase underline decoration-rule-strong underline-offset-4 hover:text-ink">{{ __('analysis.item.criteria') }}</summary>
                <dl class="mt-2 space-y-3">
                    @foreach ([\App\Enums\RuleStatus::Addressed, \App\Enums\RuleStatus::PartiallyAddressed, \App\Enums\RuleStatus::NotFound] as $status)
                        <div>
                            <dt><x-status-badge :status="$status" /></dt>
                            <dd class="mt-1 text-ink-soft">{{ $rule->criteria->for($status)?->in($locale) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </details>
        </div>
    </div>
</details>
