<?php

use Illuminate\Support\Facades\Route;
use Files13\Http\Controllers\FileController;
use Files13\Http\Controllers\PhaseController;
use Files13\Http\Controllers\DocumentController;
use Files13\Http\Controllers\FileTypeController;
use Files13\Http\Controllers\FilePhaseController;
use Files13\Http\Controllers\FileTrashController;
use Files13\Http\Controllers\FileEndingController;
use Files13\Http\Controllers\FileStatusController;
use Files13\Http\Controllers\OnlyOfficeController;
use Files13\Http\Controllers\PhaseTrashController;
use Files13\Http\Controllers\FileSubjectController;
use Files13\Http\Controllers\FileArchivedController;
use Files13\Http\Controllers\FileDocumentController;
use Files13\Http\Controllers\FileLocationController;
use Files13\Http\Controllers\DocumentTrashController;
use Files13\Http\Controllers\FileTypeTrashController;
use Files13\Http\Controllers\PhaseArchivedController;
use Files13\Http\Controllers\FileEndingTrashController;
use Files13\Http\Controllers\FileStatusTrashController;
use Files13\Http\Controllers\DocumentArchivedController;
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

    Route::get('phases', [PhaseController::class, 'index'])->name('phases.index');
    Route::post('phases', [PhaseController::class, 'store'])->name('phases.store');
    Route::put('phases/{phase}', [PhaseController::class, 'update'])->whereNumber('phase')->name('phases.update');
    Route::delete('phases/{phase}', [PhaseController::class, 'destroy'])->whereNumber('phase')->name('phases.destroy');
    Route::get('phases/options', [PhaseController::class, 'selectOptions'])->name('phases.options')->middleware('throttle:60,1');
    Route::get('phases/archived', [PhaseArchivedController::class, 'index'])->name('phases.archived.index');
    Route::patch('phases/{phase}/archive', [PhaseArchivedController::class, 'archive'])->whereNumber('phase')->name('phases.archive');
    Route::patch('phases/archived/{phase}', [PhaseArchivedController::class, 'activate'])->whereNumber('phase')->name('phases.archived.activate');
    Route::get('phases/trash', [PhaseTrashController::class, 'index'])->name('phases.trash.index');
    Route::patch('phases/trash/{phase}', [PhaseTrashController::class, 'restore'])->whereNumber('phase')->name('phases.trash.restore');
    Route::delete('phases/trash/{phase}', [PhaseTrashController::class, 'destroy'])->whereNumber('phase')->name('phases.trash.destroy');

    Route::get('templates', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('templates', [DocumentController::class, 'store'])->name('documents.store');
    Route::put('templates/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('documents.update');
    Route::delete('templates/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('documents.destroy');
    Route::get('templates/archived', [DocumentArchivedController::class, 'index'])->name('documents.archived.index');
    Route::patch('templates/{document}/archive', [DocumentArchivedController::class, 'archive'])->whereNumber('document')->name('documents.archive');
    Route::patch('templates/archived/{document}', [DocumentArchivedController::class, 'activate'])->whereNumber('document')->name('documents.archived.activate');
    Route::get('templates/trash', [DocumentTrashController::class, 'index'])->name('documents.trash.index');
    Route::patch('templates/trash/{document}', [DocumentTrashController::class, 'restore'])->whereNumber('document')->name('documents.trash.restore');
    Route::delete('templates/trash/{document}', [DocumentTrashController::class, 'destroy'])->whereNumber('document')->name('documents.trash.destroy');

    Route::get('/', [FileController::class, 'index'])->name('files.index');
    Route::get('mine', [FileController::class, 'mine'])->name('files.mine');
    Route::post('/', [FileController::class, 'store'])->name('files.store');
    Route::get('{file}', [FileController::class, 'show'])->whereNumber('file')->name('files.show');
    Route::put('{file}', [FileController::class, 'update'])->whereNumber('file')->name('files.update');
    Route::delete('{file}', [FileController::class, 'destroy'])->whereNumber('file')->name('files.destroy');
    Route::get('phases-of-files', [FilePhaseController::class, 'index'])->name('file-phases.index');
    Route::get('phases-of-files/mine', [FilePhaseController::class, 'mine'])->name('file-phases.mine');
    Route::put('{file}/phases', [FilePhaseController::class, 'update'])->whereNumber('file')->name('files.phases.update');
    Route::post('{file}/documents', [FileDocumentController::class, 'store'])->whereNumber('file')->name('files.documents.store');
    Route::patch('{file}/documents/{document}', [FileDocumentController::class, 'update'])->whereNumber(['file', 'document'])->name('files.documents.update');
    Route::delete('{file}/documents/{document}', [FileDocumentController::class, 'destroy'])->whereNumber(['file', 'document'])->name('files.documents.destroy');
    Route::get('documents/{document}/editor', [OnlyOfficeController::class, 'editor'])->whereNumber('document')->name('documents.editor');
    Route::get('archived', [FileArchivedController::class, 'index'])->name('files.archived.index');
    Route::patch('{file}/archive', [FileArchivedController::class, 'archive'])->whereNumber('file')->name('files.archive');
    Route::patch('archived/{file}', [FileArchivedController::class, 'activate'])->whereNumber('file')->name('files.archived.activate');
    Route::get('trash', [FileTrashController::class, 'index'])->name('files.trash.index');
    Route::patch('trash/{file}', [FileTrashController::class, 'restore'])->whereNumber('file')->name('files.trash.restore');
    Route::delete('trash/{file}', [FileTrashController::class, 'destroy'])->whereNumber('file')->name('files.trash.destroy');
});
