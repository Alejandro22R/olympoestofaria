<?php

use App\Http\Controllers\Admin\ShowCustomerDocument;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLocale::class)->group(function () {
    Route::view('/', 'portal.home')->name('home');
});

// Archivos privados del panel: URL firmada + sesión + Policy.
Route::middleware(['auth', 'signed'])->prefix('admin/private')->group(function () {
    Route::get('customer-documents/{document}', ShowCustomerDocument::class)->name('admin.customer-documents.show');
});
