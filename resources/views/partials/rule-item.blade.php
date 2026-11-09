@php
    /** @var \App\Domain\Reporting\ReportItem $item */
    /** @var string $policyLanguage 'id' or 'en': the language to show example wording in */
    $locale = app()->getLocale();
    $rule = $item->rule;
@endphp

<details id="rule-{{ $rule->id }}" class="group rounded-lg border border-slate-200 bg-white open:border-slate-300 open:shadow-sm">
    <summary class="flex min-h-12 cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-2 rounded-lg p-4 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
        <svg aria-hidden="true" viewBox="0 0 16 16" class="size-4 shrink-0 text-slate-500 transition-transform group-open:rotate-90" fill="currentColor"><path d="M6 3.5 10.5 8 6 12.5z"/></svg>
        <x-status-badge :status="$item->status" />
        <span class="min-w-0 flex-1 font-medium text-slate-900">{{ $rule->title->in($locale) }}</span>
        <span class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
            @if ($rule->isInterpretive())
                <span class="rounded border border-slate-300 px-1.5 py-0.5">{{ __('analysis.interpretive') }}</span>
            @endif
            <span class="font-mono">{{ $rule->id }}</span>
        </span>
    </summary>

    <div class="space-y-6 border-t border-slate-200 p-4 sm:p-6">
        <p class="text-slate-700">{{ $rule->explanation->in($locale) }}</p>

        @if ($item->notApplicableBecause !== [])
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('analysis.item.not_applicable_because') }}</h3>
                <ul class="mt-2 list-disc space-y-1 ps-5 text-slate-700">
                    @foreach ($item->notApplicableBecause as $answer)
                        <li>{{ __('analysis.item.answer', ['question' => $answer->question->question->in($locale), 'answer' => __('home.answers.'.$answer->answer->value)]) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($item->reason)
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('analysis.item.reason') }}</h3>
                <p class="mt-1 text-slate-800">{{ $item->reason->in($locale) }}</p>
                @if ($item->source === \App\Enums\EvaluationSource::LanguageCheck)
                    <p class="mt-1 text-sm text-slate-600">{{ __('analysis.item.language_check') }}</p>
                @endif
            </div>
        @endif

        @if ($item->wasDowngraded() && $item->modelStatus)
            <p class="rounded-md border border-violet-300 bg-violet-50 p-3 text-violet-900">
                {{ __('analysis.item.downgraded.'.$item->verification->downgrade->value, ['status' => __('analysis.status.'.$item->modelStatus->value)]) }}
            </p>
        @endif

        @if ($item->applicable && $item->source !== \App\Enums\EvaluationSource::LanguageCheck)
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('analysis.item.evidence') }}</h3>
                @forelse ($item->quotes as $quote)
                    <figure class="mt-3">
                        <blockquote class="quote text-slate-800" lang="{{ $policyLanguage }}">{{ $quote->quote }}</blockquote>
                        <figcaption class="mt-1 text-sm text-slate-600">
                            @if ($quote->verified)
                                {{ __('analysis.item.section', ['section' => $quote->sectionId ?? $quote->citedSectionId]) }}
                                @if ($quote->sectionWasCorrected())
                                    · {{ __('analysis.item.section_corrected', ['actual' => $quote->sectionId, 'cited' => $quote->citedSectionId]) }}
                                @endif
                            @else
                                <span class="font-medium text-violet-800">{{ __('analysis.item.unverified') }}</span>
                            @endif
                        </figcaption>
                    </figure>
                @empty
                    <p class="mt-1 text-slate-600">{{ __('analysis.item.no_evidence') }}</p>
                @endforelse
            </div>
        @endif

        <div>
            <h3 class="font-semibold text-slate-900">{{ __('analysis.item.look_for') }}</h3>
            <ul class="mt-2 list-disc space-y-1 ps-5 text-slate-700">
                @foreach ($rule->lookFor->in($locale) as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>

        @if ($item->hasGap())
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('analysis.item.next_step') }}</h3>
                <p class="mt-1 text-slate-800">{{ $rule->nextStep->in($locale) }}</p>
            </div>

            <div class="rounded-md border border-dashed border-slate-400 bg-slate-50 p-4">
                <h3 class="flex flex-wrap items-center gap-2 font-semibold text-slate-900">
                    {{ __('analysis.item.example') }}
                    <span class="rounded bg-amber-100 px-2 py-0.5 text-sm font-medium text-amber-900">{{ __('analysis.item.example_label') }}</span>
                </h3>
                <p class="mt-2 text-slate-800" lang="{{ $policyLanguage }}">{{ $rule->exampleWording->in($policyLanguage) }}</p>
            </div>
        @endif

        @if ($rule->interpretationNote)
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('analysis.item.interpretation') }}</h3>
                <p class="mt-1 text-slate-700">{{ $rule->interpretationNote->in($locale) }}</p>
            </div>
        @endif

        @if ($item->override)
            <div>
                <h3 class="font-semibold text-slate-900">{{ __('analysis.item.override') }}</h3>
                <p class="mt-1 text-slate-700">{{ $item->override->reason->in($locale) }}</p>
            </div>
        @endif

        @foreach (['basis' => $item->basisReferences(), 'related' => $item->relatedReferences()] as $role => $references)
            @if ($references !== [])
                <div>
                    <h3 class="font-semibold text-slate-900">{{ __('analysis.item.'.$role) }}</h3>
                    <ul class="mt-2 space-y-3">
                        @foreach ($references as $reference)
                            <li class="text-slate-800">
                                <span class="font-medium">{{ $reference->provision->citation->in($locale) }}</span>
                                @if ($reference->pinpoint)
                                    <span>({{ $reference->pinpoint->in($locale) }})</span>
                                @endif
                                @if ($reference->provision->modifiedBy)
                                    <span class="text-sm text-slate-600">{{ __('analysis.item.modified_by', ['reference' => $reference->provision->modifiedBy]) }}</span>
                                @endif
                                <span class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    @if ($reference->officialUrl)
                                        <a href="{{ $reference->officialUrl }}" rel="noopener noreferrer" class="text-accent-700 underline hover:text-accent-800">{{ __('analysis.item.official_text', ['page' => $reference->page()]) }}</a>
                                    @endif
                                    @if ($reference->readerUrl)
                                        <a href="{{ $reference->readerUrl }}" rel="noopener noreferrer" class="text-accent-700 underline hover:text-accent-800">{{ __('analysis.item.reader') }}</a>
                                    @endif
                                </span>
                                <details class="mt-2">
                                    <summary class="min-h-11 cursor-pointer text-sm font-medium text-slate-700 underline">{{ __('analysis.item.provision_text') }}</summary>
                                    <blockquote class="quote mt-2 text-sm text-slate-800" lang="id">{{ $reference->provision->text->id }}</blockquote>
                                    @if ($locale !== 'id')
                                        <p class="mt-2 text-sm text-slate-600">{{ __('analysis.item.unofficial_translation') }} {{ $reference->provision->text->en }}</p>
                                    @endif
                                </details>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        <details>
            <summary class="min-h-11 cursor-pointer text-sm font-medium text-slate-700 underline">{{ __('analysis.item.criteria') }}</summary>
            <dl class="mt-2 space-y-2 text-sm">
                @foreach ([\App\Enums\RuleStatus::Addressed, \App\Enums\RuleStatus::PartiallyAddressed, \App\Enums\RuleStatus::NotFound] as $status)
                    <div>
                        <dt class="font-medium text-slate-900">{{ __('analysis.status.'.$status->value) }}</dt>
                        <dd class="text-slate-700">{{ $rule->criteria->for($status)?->in($locale) }}</dd>
                    </div>
                @endforeach
            </dl>
        </details>
    </div>
</details>
