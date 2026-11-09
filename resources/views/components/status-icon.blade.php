@props(['status'])

{{-- Decorative ballot-box glyphs; the status is always written out next to them. --}}
<svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" {{ $attributes }}>
    @switch($status)
        @case('addressed')
        @case('done')
            <rect x="1.25" y="1.25" width="13.5" height="13.5" fill="currentColor" />
            <path d="m4.5 8.25 2.4 2.4 4.6-5.1" stroke="var(--color-sheet)" stroke-width="1.75" stroke-linecap="square" />
            @break
        @case('partially_addressed')
            <rect x="1.25" y="1.25" width="13.5" height="13.5" />
            <path d="M1.25 1.25h13.5L1.25 14.75z" fill="currentColor" stroke="none" />
            @break
        @case('not_found')
            <rect x="1.25" y="1.25" width="13.5" height="13.5" />
            @break
        @case('needs_human_review')
            <rect x="1.25" y="1.25" width="13.5" height="13.5" stroke-dasharray="2.5 1.75" />
            <path d="M6.2 6.1a1.85 1.85 0 1 1 2.6 1.7c-.5.25-.8.7-.8 1.25v.35" stroke-linecap="round" />
            <path d="M8 11.4v.1" stroke-width="2" stroke-linecap="round" />
            @break
        @case('current')
            <rect x="1.25" y="1.25" width="13.5" height="13.5" />
            <rect x="5" y="5" width="6" height="6" fill="currentColor" stroke="none" class="pulse" />
            @break
        @case('pending')
            <rect x="1.25" y="1.25" width="13.5" height="13.5" stroke-dasharray="2.5 1.75" />
            @break
        @default
            <path d="M3.5 8h9" stroke-width="1.75" />
    @endswitch
</svg>
