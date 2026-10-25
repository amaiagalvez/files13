<x-layouts::app :title="__('Phases of the files')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
            <div class="min-w-0">
                <flux:heading level="1" size="xl" data-test="file-phases-heading">{{ __('Phases of the files') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('The nearest alert first.') }}</flux:text>
            </div>
            <flux:button size="sm" variant="ghost" :href="$mine ? route('file-phases.index') : route('file-phases.mine')"
                :icon="$mine ? 'squares-2x2' : 'user'" data-test="file-phases-mine-toggle">
                {{ $mine ? __('All files') : __('My files') }}
            </flux:button>
        </div>

        <form method="GET" class="max-w-sm" role="search">
            <flux:input name="search" :value="$search" icon="magnifying-glass" :placeholder="__('Search by file')"
                :aria-label="__('Search by file')" data-test="file-phases-search" />
        </form>

        <flux:table :paginate="$phases" data-test="file-phases-list">
            <flux:table.columns>
                <flux:table.column>{{ __('File') }}</flux:table.column>
                <flux:table.column>{{ __('Phase') }}</flux:table.column>
                <flux:table.column>{{ __('Responsible') }}</flux:table.column>
                <flux:table.column>{{ __('Start') }}</flux:table.column>
                <flux:table.column>{{ __('End') }}</flux:table.column>
                <flux:table.column>{{ __('Alert date') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($phases as $row)
                    <flux:table.row :key="$row->id" data-test="file-phase-row-{{ $row->id }}">
                        <flux:table.cell>
                            <a href="{{ route('files.show', $row->file_id) }}" class="hover:underline">{{ $row->file->name }}</a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $row->phase->name_eu }}</flux:table.cell>
                        <flux:table.cell>{{ $row->responsible?->getAttribute('name') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $row->start_date?->toDateString() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $row->end_date?->toDateString() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $row->alert_date?->toDateString() ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">{{ __('No phase matches.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</x-layouts::app>
