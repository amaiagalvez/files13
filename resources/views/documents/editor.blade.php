<x-layouts::app :title="$document->name">
    <div class="flex h-full flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
            <flux:heading level="1" size="xl" data-test="document-editor-heading">{{ $document->name }}</flux:heading>
            <flux:button variant="ghost" icon="arrow-left" :href="url()->previous()" data-test="document-editor-back">{{ __('Back') }}</flux:button>
        </div>

        @if ($config === null)
            <flux:callout variant="danger" icon="exclamation-triangle" data-test="document-editor-unconfigured">
                {{ __('The document editor is not configured.') }}
            </flux:callout>
        @elseif ($document->path === null)
            <flux:callout icon="information-circle" data-test="document-editor-empty">
                {{ __('The document has no content yet. Upload one to edit it.') }}
            </flux:callout>
        @else
            @if ($config['editorConfig']['mode'] === 'view')
                <flux:text data-test="document-editor-read-only">{{ __('You can read this document but not change it.') }}</flux:text>
            @endif
            <div id="onlyoffice-editor" class="min-h-[70vh] flex-1" data-test="document-editor"></div>
            <flux:callout id="onlyoffice-unreachable" class="hidden" variant="danger" icon="exclamation-triangle">
                {{ __('The document server cannot be reached.') }}
            </flux:callout>
            <script src="{{ $server }}/web-apps/apps/api/documents/api.js"
                onerror="document.getElementById('onlyoffice-unreachable').classList.remove('hidden')"></script>
            <script>
                if (window.DocsAPI) {
                    new window.DocsAPI.DocEditor('onlyoffice-editor', @js($config));
                }
            </script>
        @endif
    </div>
</x-layouts::app>
