@props(['status' => null])

{{-- A result status, always shown as glyph + text, never by colour alone. --}}
@php
    $key = $status instanceof \App\Enums\RuleStatus ? $status->value : 'not_applicable';
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center gap-2 text-sm font-medium', 'status-'.$key]) }}>
    <x-status-icon :status="$key" class="size-4 shrink-0" />
    <span>{{ __('analysis.status.'.$key) }}</span>
</span>
