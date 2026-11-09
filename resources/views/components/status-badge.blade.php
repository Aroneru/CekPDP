@props(['status' => null])

{{-- A result status, always shown as icon + text, never by colour alone. --}}
@php
    $key = $status instanceof \App\Enums\RuleStatus ? $status->value : 'not_applicable';

    $classes = [
        'addressed' => 'bg-emerald-50 text-emerald-800 ring-emerald-700/25',
        'partially_addressed' => 'bg-amber-50 text-amber-900 ring-amber-700/30',
        'not_found' => 'bg-rose-50 text-rose-800 ring-rose-700/25',
        'needs_human_review' => 'bg-violet-50 text-violet-800 ring-violet-700/25',
        'not_applicable' => 'bg-slate-100 text-slate-700 ring-slate-600/25',
    ][$key];
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-medium ring-1 ring-inset', $classes]) }}>
    <x-status-icon :status="$key" class="size-4" />
    <span>{{ __('analysis.status.'.$key) }}</span>
</span>
