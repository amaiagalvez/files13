@php
    $editingFileLocation = $list['create'] && str_starts_with(old('_file_location_form', ''), 'edit-');
    $deletedFileLocationConflict = session('deleted_file_location_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedFileLocationConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingFileLocation ? old('_file_location_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'days' => array_values((array) old('days', [])),
            'context' => $editingFileLocation ? old('_file_location_form') : 'create',
            'method' => $editingFileLocation ? 'PUT' : 'POST',
            'action' => $editingFileLocation
                ? route('file-locations.update', old('_file_location_id'))
                : route('file-locations.store'),
            'title' => $editingFileLocation ? __('Edit location') : __('New location'),
            'submitLabel' => $editingFileLocation ? __('Update location') : __('Save location'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('file-locations.store')),
        updateUrl: @js(route('file-locations.update', '__RECORD__')),
        createLocation() {
            this.form = {
                id: null,
                name_eu: '',
                name_es: '',
                name_fr: '',
                name_en: '',
                notes: '',
                days: [],
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New location')),
                submitLabel: @js(__('Save location')),
            };
        },
        editLocation(fileLocation) {
            this.form = {
                ...fileLocation,
                context: `edit-${fileLocation.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', fileLocation.id),
                title: @js(__('Edit location')),
                submitLabel: @js(__('Update location')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-location-form' }))"
        @elseif ($deletedFileLocationConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-location-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createLocation(); $nextTick(() => $dispatch('modal-show', { name: 'file-location-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="file-location" :description="__('Calendars of days that files can be tied to.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="file-location" :label="__('New location')"
                        click="createLocation()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="file-location" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$fileLocations->total()" :scope="$scope">
                <x-basics13::list.table prefix="file-location" :paginator="$fileLocations">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="file-location" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name (eu)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (es)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (fr)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (en)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Days') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate" title="{{ $row['nameEu'] }}"
                                        data-test="file-location-name-{{ $row['id'] }}">
                                        {{ $row['nameEu'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEs'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameFr'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEn'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell data-test="file-location-days-{{ $row['id'] }}">
                                    {{ $row['daysCount'] }}
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="file-location"
                                        edit-handler="editLocation" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="6" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="file-location-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createLocation()"
                                            data-test="file-location-empty-create">
                                            {{ __('New location') }}
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
            <flux:modal name="file-location-form" aria-labelledby="file-location-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('files13::file-locations.form')
            </flux:modal>

            @if ($deletedFileLocationConflict)
                <x-basics13::name-conflict-modal name="file-location-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedFileLocationConflict['name_eu'],
                    ])" :create-action="route('file-locations.store')" :restore-action="route(
                        'file-locations.trash.restore',
                        $deletedFileLocationConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new location')" :restore-label="__('Restore the deleted record instead')"
                    create-test="file-location-conflict-create-new"
                    restore-test="file-location-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="file-location" />
    </div>
</x-layouts::app>