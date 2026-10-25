<x-layouts::app :title="__('Case files')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
            <div class="min-w-0">
                <flux:heading level="1" size="xl" data-test="files-home-heading">{{ __('Case files') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Your files and the alerts of their open phases.') }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" icon="calendar-days" :href="route('files-calendar')" data-test="files-home-calendar">{{ __('Calendar') }}</flux:button>
                <flux:button variant="primary" icon="folder-open" :href="route('files.mine')" data-test="files-home-mine">{{ __('My files') }}</flux:button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section aria-labelledby="files-home-mine-heading">
                <flux:heading id="files-home-mine-heading" level="2" size="lg">{{ __('My files') }}</flux:heading>
                <ul class="mt-3 flex flex-col gap-2" data-test="files-home-files">
                    @forelse ($files as $file)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('files.show', $file) }}" class="truncate hover:underline">{{ $file->name }}</a>
                            <flux:text class="shrink-0 text-xs">{{ $file->type->name_eu }}</flux:text>
                        </li>
                    @empty
                        <li><flux:text>{{ __('You have no files yet.') }}</flux:text></li>
                    @endforelse
                </ul>
            </section>

            <section aria-labelledby="files-home-alerts-heading">
                <flux:heading id="files-home-alerts-heading" level="2" size="lg">{{ __('Upcoming alerts') }}</flux:heading>
                <ul class="mt-3 flex flex-col gap-2" data-test="files-home-alerts">
                    @forelse ($alerts as $alert)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('files.show', $alert->file_id) }}" class="truncate hover:underline">
                                {{ $alert->file->name }} · {{ $alert->phase->name_eu }}
                            </a>
                            <flux:badge size="sm" :color="$alert->alert_date?->isPast() ? 'red' : 'zinc'">{{ $alert->alert_date?->toDateString() }}</flux:badge>
                        </li>
                    @empty
                        <li><flux:text>{{ __('No alerts ahead.') }}</flux:text></li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-layouts::app>
