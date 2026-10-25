<?php

use Illuminate\Support\Facades\Route;
use Files13\Http\Controllers\FileTypeController;
use Files13\Http\Controllers\FileEndingController;
use Files13\Http\Controllers\FileStatusController;
use Files13\Http\Controllers\FileSubjectController;
use Files13\Http\Controllers\FileLocationController;
use Files13\Http\Controllers\FileTypeTrashController;
use Files13\Http\Controllers\FileEndingTrashController;
use Files13\Http\Controllers\FileStatusTrashController;
use Files13\Http\Controllers\FileSubjectTrashController;
use Files13\Http\Controllers\FileTypeArchivedController;
use Files13\Http\Controllers\FileLocationTrashController;
use Files13\Http\Controllers\FileEndingArchivedController;
use Files13\Http\Controllers\FileStatusArchivedController;
use Files13\Http\Controllers\FileSubjectArchivedController;
use Files13\Http\Controllers\FileLocationArchivedController;

/**
 * Every screen of the package lives under `files`, and keeps the bare resource names the
 * policies, menus and transformers address.
 */
Route::prefix('files')->middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::get('subjects', [FileSubjectController::class, 'index'])->name('file-subjects.index');
    Route::post('subjects', [FileSubjectController::class, 'store'])->name('file-subjects.store');
    Route::put('subjects/{file_subject}', [FileSubjectController::class, 'update'])->whereNumber('file_subject')->name('file-subjects.update');
    Route::delete('subjects/{file_subject}', [FileSubjectController::class, 'destroy'])->whereNumber('file_subject')->name('file-subjects.destroy');
    Route::get('subjects/options', [FileSubjectController::class, 'selectOptions'])->name('file-subjects.options')->middleware('throttle:60,1');
    Route::get('subjects/archived', [FileSubjectArchivedController::class, 'index'])->name('file-subjects.archived.index');
    Route::patch('subjects/{file_subject}/archive', [FileSubjectArchivedController::class, 'archive'])->whereNumber('file_subject')->name('file-subjects.archive');
    Route::patch('subjects/archived/{file_subject}', [FileSubjectArchivedController::class, 'activate'])->whereNumber('file_subject')->name('file-subjects.archived.activate');
    Route::get('subjects/trash', [FileSubjectTrashController::class, 'index'])->name('file-subjects.trash.index');
    Route::patch('subjects/trash/{file_subject}', [FileSubjectTrashController::class, 'restore'])->whereNumber('file_subject')->name('file-subjects.trash.restore');
    Route::delete('subjects/trash/{file_subject}', [FileSubjectTrashController::class, 'destroy'])->whereNumber('file_subject')->name('file-subjects.trash.destroy');

    Route::get('types', [FileTypeController::class, 'index'])->name('file-types.index');
    Route::post('types', [FileTypeController::class, 'store'])->name('file-types.store');
    Route::put('types/{file_type}', [FileTypeController::class, 'update'])->whereNumber('file_type')->name('file-types.update');
    Route::delete('types/{file_type}', [FileTypeController::class, 'destroy'])->whereNumber('file_type')->name('file-types.destroy');
    Route::get('types/options', [FileTypeController::class, 'selectOptions'])->name('file-types.options')->middleware('throttle:60,1');
    Route::get('types/archived', [FileTypeArchivedController::class, 'index'])->name('file-types.archived.index');
    Route::patch('types/{file_type}/archive', [FileTypeArchivedController::class, 'archive'])->whereNumber('file_type')->name('file-types.archive');
    Route::patch('types/archived/{file_type}', [FileTypeArchivedController::class, 'activate'])->whereNumber('file_type')->name('file-types.archived.activate');
    Route::get('types/trash', [FileTypeTrashController::class, 'index'])->name('file-types.trash.index');
    Route::patch('types/trash/{file_type}', [FileTypeTrashController::class, 'restore'])->whereNumber('file_type')->name('file-types.trash.restore');
    Route::delete('types/trash/{file_type}', [FileTypeTrashController::class, 'destroy'])->whereNumber('file_type')->name('file-types.trash.destroy');

    Route::get('statuses', [FileStatusController::class, 'index'])->name('file-statuses.index');
    Route::post('statuses', [FileStatusController::class, 'store'])->name('file-statuses.store');
    Route::put('statuses/{file_status}', [FileStatusController::class, 'update'])->whereNumber('file_status')->name('file-statuses.update');
    Route::delete('statuses/{file_status}', [FileStatusController::class, 'destroy'])->whereNumber('file_status')->name('file-statuses.destroy');
    Route::get('statuses/options', [FileStatusController::class, 'selectOptions'])->name('file-statuses.options')->middleware('throttle:60,1');
    Route::get('statuses/archived', [FileStatusArchivedController::class, 'index'])->name('file-statuses.archived.index');
    Route::patch('statuses/{file_status}/archive', [FileStatusArchivedController::class, 'archive'])->whereNumber('file_status')->name('file-statuses.archive');
    Route::patch('statuses/archived/{file_status}', [FileStatusArchivedController::class, 'activate'])->whereNumber('file_status')->name('file-statuses.archived.activate');
    Route::get('statuses/trash', [FileStatusTrashController::class, 'index'])->name('file-statuses.trash.index');
    Route::patch('statuses/trash/{file_status}', [FileStatusTrashController::class, 'restore'])->whereNumber('file_status')->name('file-statuses.trash.restore');
    Route::delete('statuses/trash/{file_status}', [FileStatusTrashController::class, 'destroy'])->whereNumber('file_status')->name('file-statuses.trash.destroy');

    Route::get('endings', [FileEndingController::class, 'index'])->name('file-endings.index');
    Route::post('endings', [FileEndingController::class, 'store'])->name('file-endings.store');
    Route::put('endings/{file_ending}', [FileEndingController::class, 'update'])->whereNumber('file_ending')->name('file-endings.update');
    Route::delete('endings/{file_ending}', [FileEndingController::class, 'destroy'])->whereNumber('file_ending')->name('file-endings.destroy');
    Route::get('endings/options', [FileEndingController::class, 'selectOptions'])->name('file-endings.options')->middleware('throttle:60,1');
    Route::get('endings/archived', [FileEndingArchivedController::class, 'index'])->name('file-endings.archived.index');
    Route::patch('endings/{file_ending}/archive', [FileEndingArchivedController::class, 'archive'])->whereNumber('file_ending')->name('file-endings.archive');
    Route::patch('endings/archived/{file_ending}', [FileEndingArchivedController::class, 'activate'])->whereNumber('file_ending')->name('file-endings.archived.activate');
    Route::get('endings/trash', [FileEndingTrashController::class, 'index'])->name('file-endings.trash.index');
    Route::patch('endings/trash/{file_ending}', [FileEndingTrashController::class, 'restore'])->whereNumber('file_ending')->name('file-endings.trash.restore');
    Route::delete('endings/trash/{file_ending}', [FileEndingTrashController::class, 'destroy'])->whereNumber('file_ending')->name('file-endings.trash.destroy');

    Route::get('locations', [FileLocationController::class, 'index'])->name('file-locations.index');
    Route::post('locations', [FileLocationController::class, 'store'])->name('file-locations.store');
    Route::put('locations/{file_location}', [FileLocationController::class, 'update'])->whereNumber('file_location')->name('file-locations.update');
    Route::delete('locations/{file_location}', [FileLocationController::class, 'destroy'])->whereNumber('file_location')->name('file-locations.destroy');
    Route::get('locations/options', [FileLocationController::class, 'selectOptions'])->name('file-locations.options')->middleware('throttle:60,1');
    Route::get('locations/archived', [FileLocationArchivedController::class, 'index'])->name('file-locations.archived.index');
    Route::patch('locations/{file_location}/archive', [FileLocationArchivedController::class, 'archive'])->whereNumber('file_location')->name('file-locations.archive');
    Route::patch('locations/archived/{file_location}', [FileLocationArchivedController::class, 'activate'])->whereNumber('file_location')->name('file-locations.archived.activate');
    Route::get('locations/trash', [FileLocationTrashController::class, 'index'])->name('file-locations.trash.index');
    Route::patch('locations/trash/{file_location}', [FileLocationTrashController::class, 'restore'])->whereNumber('file_location')->name('file-locations.trash.restore');
    Route::delete('locations/trash/{file_location}', [FileLocationTrashController::class, 'destroy'])->whereNumber('file_location')->name('file-locations.trash.destroy');
});
