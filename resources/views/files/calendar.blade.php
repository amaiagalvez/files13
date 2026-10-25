@php
    // At most this many phases per day; the rest are counted, as in the other calendars.
    $cellLimit = 3;
    $days = [];
    for ($day = $from; $day->lte($to); $day = $day->addDay()) {
        $days[] = $day;
    }
@endphp

<x-layouts::app :title="__('Calendar')">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
            <flux:heading level="1" size="xl" data-test="files-calendar-heading">{{ __('Calendar') }} · {{ $month->translatedFormat('F Y') }}</flux:heading>
            <div class="flex gap-2">
                <flux:button size="sm" variant="ghost" icon="chevron-left" :href="route('files-calendar', ['month' => $month->subMonth()->format('Y-m')])"
                    :aria-label="__('Previous month')" data-test="files-calendar-previous" />
                <flux:button size="sm" variant="ghost" :href="route('files-calendar')" data-test="files-calendar-today">{{ __('Today') }}</flux:button>
                <flux:button size="sm" variant="ghost" icon="chevron-right" :href="route('files-calendar', ['month' => $month->addMonth()->format('Y-m')])"
                    :aria-label="__('Next month')" data-test="files-calendar-next" />
            </div>
        </div>

        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-lg border border-zinc-200 bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-700" data-test="files-calendar">
            @foreach ($days as $day)
                @php
                    $dayEvents = $events->filter(fn (array $event): bool => $event['start']->lte($day) && $event['end']->gte($day))->values();
                @endphp
                <div class="min-h-24 bg-white p-1 text-xs dark:bg-zinc-900 {{ $day->month !== $month->month ? 'opacity-50' : '' }}"
                    data-test="files-calendar-day-{{ $day->toDateString() }}">
                    <div class="font-medium {{ $day->isToday() ? 'text-blue-600' : '' }}">{{ $day->day }}</div>
                    @foreach ($dayEvents->take($cellLimit) as $event)
                        <a href="{{ route('files.show', $event['file_id']) }}" class="mt-1 flex items-center gap-1 truncate hover:underline"
                            title="{{ $event['file'] }} · {{ $event['phase'] }}" data-test="files-calendar-event-{{ $event['id'] }}">
                            <span class="size-2 shrink-0 rounded-full bg-zinc-400" @if ($event['color']) style="background-color: {{ $event['color'] }}" @endif aria-hidden="true"></span>
                            <span class="truncate">{{ $event['phase'] }} · {{ $event['file'] }}</span>
                        </a>
                    @endforeach
                    @if ($dayEvents->count() > $cellLimit)
                        <div class="mt-1 text-zinc-500">{{ __('+:count more', ['count' => $dayEvents->count() - $cellLimit]) }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-layouts::app>
