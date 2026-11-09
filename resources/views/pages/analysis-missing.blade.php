<x-message-page :title="__('analysis.missing.title')" eyebrow="404">
    {{ __('analysis.missing.body', ['minutes' => $retentionMinutes]) }}
</x-message-page>
