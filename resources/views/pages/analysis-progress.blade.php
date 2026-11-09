<x-layout :title="__('analysis.progress.title')" noindex>
    @push('head')
        <noscript><meta http-equiv="refresh" content="5"></noscript>
    @endpush

    <livewire:analysis-progress :analysis-id="$analysis->id" />
</x-layout>
