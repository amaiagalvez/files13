<?php

use Illuminate\Support\Facades\Route;
use Files13\Http\Controllers\OnlyOfficeController;
use Illuminate\Routing\Middleware\SubstituteBindings;

/**
 * The document server calls these two server to server: there is no session and no CSRF token, so
 * they stay out of the `web` group. Each one needs a trusted address and the signed token the
 * editor page put in its URL; the document is bound before the token is checked against it.
 */
Route::prefix('files/onlyoffice')->middleware(['files13.trusted-ip', SubstituteBindings::class, 'files13.onlyoffice-token'])->group(function (): void {
    Route::get('{document}', [OnlyOfficeController::class, 'get'])->whereNumber('document')->name('onlyoffice.get');
    Route::post('{document}/callback', [OnlyOfficeController::class, 'callback'])->whereNumber('document')->name('onlyoffice.callback');
});
