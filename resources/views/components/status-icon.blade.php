@props(['status'])

{{-- Decorative: the status is always written out next to the icon. --}}
<svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    @switch($status)
        @case('addressed')
            <circle cx="8" cy="8" r="6.5" />
            <path d="m5.25 8.25 1.9 1.9 3.6-3.9" />
            @break
        @case('partially_addressed')
            <circle cx="8" cy="8" r="6.5" />
            <path d="M8 1.5a6.5 6.5 0 0 1 0 13z" fill="currentColor" />
            @break
        @case('not_found')
            <circle cx="8" cy="8" r="6.5" />
            @break
        @case('needs_human_review')
            <circle cx="8" cy="8" r="6.5" />
            <path d="M6.3 6.2a1.8 1.8 0 1 1 2.5 1.65c-.5.22-.8.7-.8 1.25v.4" />
            <path d="M8 11.6v.01" stroke-width="2.25" />
            @break
        @case('done')
            <circle cx="8" cy="8" r="6.5" fill="currentColor" stroke="currentColor" />
            <path d="m5.25 8.25 1.9 1.9 3.6-3.9" stroke="white" />
            @break
        @case('current')
            <circle cx="8" cy="8" r="6.5" />
            <circle cx="8" cy="8" r="2.75" fill="currentColor" />
            @break
        @default
            <circle cx="8" cy="8" r="6.5" stroke-dasharray="2 2.4" />
    @endswitch
</svg>
