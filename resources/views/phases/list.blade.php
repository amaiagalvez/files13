@php
    $editingPhase = $list['create'] && str_starts_with(old('_phase_form', ''), 'edit-');
    $deletedPhaseConflict = session('deleted_phase_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedPhaseConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingPhase ? old('_phase_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'order' => old('order', 0),
            'color' => old('color', ''),
            'show_calendar' => (bool) old('show_calendar', false),
            'accumulate_days' => (bool) old('accumulate_days', false),
            'accumulate_days_order' => old('accumulate_days_order', ''),
            'type_ids' => array_values(array_map('strval', (array) old('type_ids', []))),
            'subjects' => array_values((array) old('subjects', [])),
            'notes' => old('notes', ''),
            'context' => $editingPhase ? old('_phase_form') : 'create',
            'method' => $editingPhase ? 'PUT' : 'POST',
            'action' => $editingPhase
                ? route('phases.update', old('_phase_id'))
                : route('phases.store'),
            'title' => $editingPhase ? __('Edit phase') : __('New phase'),
            'submitLabel' => $editingPhase ? __('Update phase') : __('Save phase'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('phases.store')),
        updateUrl: @js(route('phases.update', '__RECORD__')),
        options: @js($options ?? ['types' => [], 'subjects' => []]),
        createPhase() {
            this.form = {
                id: null,
                name_eu: '',
                name_es: '',
                name_fr: '',
                name_en: '',
                order: 0,
                color: '',
                show_calendar: false,
                accumulate_days: false,
                accumulate_days_order: '',
                type_ids: [],
                subjects: [],
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New phase')),
                submitLabel: @js(__('Save phase')),
            };
        },
        editPhase(phase) {
            this.form = {
                ...phase,
                context: `edit-${phase.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', phase.id),
                title: @js(__('Edit phase')),
                submitLabel: @js(__('Update phase')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'phase-form' }))"
        @elseif ($deletedPhaseConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'phase-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createPhase(); $nextTick(() => $dispatch('modal-show', { name: 'phase-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="phase" :description="__('Steps a file goes through, in order.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="phase" :label="__('New phase')"
                        click="createPhase()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="phase" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$phases->total()" :scope="$scope">
                <x-basics13::list.table prefix="phase" :paginator="$phases">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="phase" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Order') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (eu)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (es)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Types') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Calendar') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell data-test="phase-order-{{ $row['id'] }}">{{ $row['order'] }}</flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="flex max-w-full items-center gap-2 truncate" title="{{ $row['nameEu'] }}"
                                        data-test="phase-name-{{ $row['id'] }}">
                                        @if ($row['color'])
                                            <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $row['color'] }}" aria-hidden="true"></span>
                                        @endif
                                        {{ $row['nameEu'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEs'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate" title="{{ $row['types'] }}">{{ $row['types'] ?: '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $row['showCalendar'] ? __('Yes') : __('No') }}
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="phase"
                                        edit-handler="editPhase" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="6" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="phase-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createPhase()"
                                            data-test="phase-empty-create">
                                            {{ __('New phase') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endif
                            </x-basics13::list.empty-state>
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="phase-form" aria-labelledby="phase-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('files13::phases.form')
            </flux:modal>

            @if ($deletedPhaseConflict)
                <x-basics13::name-conflict-modal name="phase-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedPhaseConflict['name_eu'],
                    ])" :create-action="route('phases.store')" :restore-action="route(
                        'phases.trash.restore',
                        $deletedPhaseConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new phase')" :restore-label="__('Restore the deleted record instead')"
                    create-test="phase-conflict-create-new"
                    restore-test="phase-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="phase" />
    </div>
</x-layouts::app>